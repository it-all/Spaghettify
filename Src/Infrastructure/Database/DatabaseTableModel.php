<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database;

use It_All\Spaghettify\Src\Infrastructure\Database\Queries\InsertBuilder;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\InsertUpdateBuilder;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\UpdateBuilder;

class DatabaseTableModel
{
    /** @var  array of column model objects */
    protected $columns;

    /** @var string  */
    protected $tableName;

    /** @var string or false if no primary key column exists */
    protected $primaryKeyColumnName;

    /** @var bool|string  */
    protected $defaultOrderByColumnName;

    /** @var bool  */
    protected $defaultOrderByAsc;

    /**
     * @var array of columnNames with UNIQUE constraint or index
     * NOTE this does not handle multi-column unique constraints
     */
    private $uniqueColumnNames;

    private $uniqueColumns;


    public function __construct(string $tableName, string $defaultOrderByColumnName = null, bool $defaultOrderByAsc = true)
    {
        $this->tableName = $tableName;
        $this->primaryKeyColumnName = false; // initialize

        $this->uniqueColumns = [];
        $this->uniqueColumnNames = [];

        // $this->primaryKeyColumnName will be updated if exists
        // $this->uniqueColumnNames added (then used to set $column->isUnique
        $this->setConstraints();

        $this->defaultOrderByColumnName = ($defaultOrderByColumnName != null) ? $defaultOrderByColumnName : $this->primaryKeyColumnName;
        $this->defaultOrderByAsc = $defaultOrderByAsc;

        // $this->uniqueColumns added
        $this->setColumns();
    }

    /** note this will set uniqueColumnNames whether they are set as a constraint or an index */
    private function setConstraints()
    {
        $q = new QueryBuilder("SELECT ccu.column_name, tc.constraint_type FROM INFORMATION_SCHEMA.constraint_column_usage ccu JOIN information_schema.table_constraints tc ON ccu.constraint_name = tc.constraint_name WHERE tc.table_name = ccu.table_name AND ccu.table_name = $1", $this->tableName);
        $qResult = $q->execute();
        while ($qRow = pg_fetch_assoc($qResult)) {
            switch($qRow['constraint_type']) {
                case 'PRIMARY KEY':
                    $this->primaryKeyColumnName = $qRow['column_name'];
                    break;
                case 'UNIQUE':
                    $this->uniqueColumnNames[] = $qRow['column_name'];
            }
        }
    }

    protected function setColumns()
    {
        $rs = Postgres::getTableMetaData($this->tableName);
        while ($columnInfo = pg_fetch_assoc($rs)) {
            $columnInfo['is_unique'] = in_array($columnInfo['column_name'], $this->uniqueColumnNames);
            $c = new DatabaseColumnModel($this, $columnInfo);
            $this->columns[] = $c;
            if ($columnInfo['is_unique']) {
                $this->uniqueColumns[] = $c;
            }
        }
    }

    // make protected since ORM does not sniff out every constraint, some must be added manually when table model is extended
    protected function addColumnConstraint(DatabaseColumnModel $column, string $constraint, $context = true)
    {
        $column->addConstraint($constraint, $context);
    }

    protected function addColumnNameConstraint(string $columName, string $constraint)
    {
        $this->addColumnConstraint($this->getColumnByName($columName), $constraint);
    }

    public function getColumnConstraints(): array
    {
        $constraints = [];
        foreach($this->columns as $column) {
            $constraints[$column->getName()] = $column->getConstraints();
        }
        return $constraints;
    }

    public function select(string $columns = '*', string $orderByColumn = null, bool $orderByAsc = true)
    {
        $q = new QueryBuilder("SELECT $columns FROM $this->tableName");
        if ($orderByColumn != null) {
            if ($orderByColumn == 'PRIMARYKEY') {
                if ($this->primaryKeyColumnName === false) {
                    throw new \Exception("Cannot order by Primary Key since it does not exist for table ".$this->tableName);
                }
                $orderByColumn = $this->primaryKeyColumnName;
            }
            $orderByString = " ORDER BY $orderByColumn";
            if (!$orderByAsc) {
                $orderByString .= " DESC";
            }
            $q->add($orderByString);
        }
        return $q->execute();
    }

    public function hasColumnValue(DatabaseColumnModel $databaseColumnModel, $value): bool
    {
        $q = new QueryBuilder("SELECT ".$this->getPrimaryKeyColumnName()." FROM $this->tableName WHERE ".$databaseColumnModel->getName()." = $1", [$value]);
        return $q->getOne();
    }

    public function selectForPrimaryKey($primaryKeyValue)
    {
        $primaryKeyName = $this->getPrimaryKeyColumnName();

        $q = new QueryBuilder("SELECT * FROM $this->tableName WHERE $primaryKeyName = $1", $primaryKeyValue);
        if(!$res = $q->execute()) {
            // this is for a query error not a not found condition
            throw new \Exception("Invalid $primaryKeyName for $this->table: $primaryKeyValue");
        }
        return pg_fetch_assoc($res); // returns false if not records are found
    }

    protected function addBooleanColumnValues(array $columnValues): array
    {
        foreach ($this->columns as $column) {
            if ($column->isBoolean() && !isset($columnValues[$column->getName()])) {
                $columnValues[$column->getName()] = 'f';
            }
        }

        return $columnValues;
    }

    public function insertRecord(array $columnValues)
    {
        $columnValues = $this->addBooleanColumnValues($columnValues);
        $ib = new InsertBuilder($this->tableName);
        $ib->setPrimaryKeyName($this->getPrimaryKeyColumnName());
        $this->addColumnsToBuilder($ib, $columnValues);
        try {
            return $ib->execute();
        } catch(\Exception $exception) {
            throw $exception;
        }
    }

    public function updateRecordByPrimaryKey(array $columnValues, $primaryKeyValue, bool $validatePrimaryKeyValue = false)
    {
        $primaryKeyName = $this->getPrimaryKeyColumnName();

        if ($validatePrimaryKeyValue && !$this->selectForPrimaryKey($primaryKeyValue)) {
            throw new \Exception("Invalid $primaryKeyName $primaryKeyValue for $this->tableName");
        }

        $columnValues = $this->addBooleanColumnValues($columnValues);
        $ub = new UpdateBuilder($this->tableName, $primaryKeyName, $primaryKeyValue);
        $this->addColumnsToBuilder($ub, $columnValues);
        return $ub->execute();
    }

    public function deleteByPrimaryKey($primaryKeyValue, string $returning = null)
    {
        $query = "DELETE FROM $this->tableName WHERE ".$this->getPrimaryKeyColumnName()." = $1";
        if ($returning !== null) {
            $query .= "RETURNING $returning";
        }
        $q = new QueryBuilder($query, $primaryKeyValue);

        return $q->execute();
    }

    private function addColumnsToBuilder(InsertUpdateBuilder $builder, array $columnValues)
    {
        foreach ($columnValues as $name => $value) {

            // make sure this is truly a column
            if ($column = $this->getColumnByName($name)) {

                if ($column->isBoolean()) {
                    $value = ($value == 'on') ? 't' : 'f';
                }

                if (is_string($value) && strlen($value) == 0) {
                    $value = $this->handleBlankValue($column);
                }
                $builder->addColumn($name, $value);
            }
        }
    }

    private function handleBlankValue(DatabaseColumnModel $column)
    {
        // set to null if field is nullable
        if ($column->getIsNullable()) {
            return null;
        }

        // set to 0 if field is numeric
        if ($column->isNumericType()) {
            return 0;
        }

        // set to f if field is boolean
        if ($column->isBoolean()) {
            return 'f';
        }

        return '';
    }

    // getters

    /**
     * @param bool $plural if false last character is removed
     * @return string
     */
    public function getFormalTableName(bool $plural = true): string
    {
        $name = ucwords(str_replace('_', ' ', $this->tableName));
        if (!$plural) {
            $name = substr($name, 0, strlen($this->tableName) - 1);
        }
        return $name;
    }

    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return string defaults to 'id', can be overridden by children
     */
    public function getPrimaryKeyColumnName(): string
    {
        return $this->primaryKeyColumnName;
    }

    public function getDefaultOrderByColumnName(): string
    {
        return $this->defaultOrderByColumnName;
    }

    public function getDefaultOrderByAsc(): bool
    {
        return $this->defaultOrderByAsc;
    }

    public function getColumns(): array
    {
        if (count($this->columns) == 0) {
            throw new \Exception('No columns in model '.$this->tableName);
        }
        return $this->columns;
    }

    public function getColumnByName(string $columnName)
    {
        foreach ($this->columns as $column) {
            if ($column->getName() == $columnName) {
                return $column;
            }
        }

        return false;
    }

    public function getUniqueColumnNames(): array
    {
        return $this->uniqueColumnNames;
    }

    public function getUniqueColumns(): array
    {
        return $this->uniqueColumns;
    }
}
