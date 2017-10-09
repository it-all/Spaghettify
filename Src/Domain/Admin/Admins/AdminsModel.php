<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\SelectBuilder;
use It_All\Spaghettify\Src\Infrastructure\ListViewModel;

class AdminsModel extends ListViewModel
{
    const LIST_VIEW_COLUMNS = [
        'id' => 'admins.id',
        'name' => 'admins.name',
        'username' => 'admins.username',
        'role' => 'roles.role',
        'level' => 'roles.level'
    ];

    public function __construct()
    {
        parent::__construct(new DatabaseTableModel('admins'));
    }

    public function insert(string $name, string $username, string $password, int $roleId)
    {
        $q = new QueryBuilder("INSERT INTO admins (name, username, password_hash, role_id) VALUES($1, $2, $3, $4) RETURNING id", $name, $username, password_hash($password, PASSWORD_DEFAULT), $roleId);
        return $q->execute();
    }

    private function getChangedColumns(array $record, string $name, string $username, int $roleId, string $password_hash = ''): array
    {
        $changedColumns = [];
        if ($name != $record['name']) {
            $changedColumns['name'] = $name;
        }
        if ($username != $record['username']) {
            $changedColumns['username'] = $username;
        }
        if ($roleId != $record['role_id']) {
            $changedColumns['role_id'] = $roleId;
        }
        if (strlen($password_hash) > 0 && $password_hash != $record['password_hash']) {
            $changedColumns['password_hash'] = $password_hash;
        }
        return $changedColumns;
    }

    // If a '' password is passed, the password field is not updated
    public function updateByPrimaryKey(int $primaryKeyValue, string $name, string $username, int $roleId, string $password = '', array $record = null)
    {
        if ($record == null && !$record = $this->selectForPrimaryKey($primaryKeyValue)) {
            throw new \Exception("Invalid Primary Key $primaryKeyValue for $this->tableName");
        }

        $changedColumns = $this->getChangedColumns($record, $name, $username, $roleId, password_hash($password, PASSWORD_DEFAULT));
        return $this->adminsTableModel->updateRecordByPrimaryKey($changedColumns, $primaryKeyValue);
    }

    public function checkRecordExistsForUsername(string $username): bool
    {
        $q = new QueryBuilder("SELECT id FROM admins WHERE username = $1", $username);
        $res = $q->execute();
        return pg_num_rows($res) > 0;
    }

    public function selectForUsername(string $username)
    {
        $q = new QueryBuilder("SELECT a.*, r.role FROM admins a JOIN roles r ON a.role_id = r.id WHERE a.username = $1", $username);
        return $q->execute();
    }

    // If a null password is passed, the password field is not checked
    public function hasRecordChanged(array $fieldValues, $primaryKeyValue, array $skipColumns = null, array $record = null): bool
    {
        if (strlen($fieldValues['password_hash']) == 0) {
            $skipColumns[] = 'password_hash';
            $skipColumns[] = 'password_hash';
        } else {
            $columnValues['password_hash'] = password_hash($fieldValues['password_hash'], PASSWORD_DEFAULT);
        }

        return $this->adminsTableModel->hasRecordChanged($fieldValues, $primaryKeyValue, $skipColumns);
    }

    public function getListView(array $whereColumnsInfo = null)
    {
        $selectClause = "SELECT ";
        $columnCount = 1;
        foreach (self::LIST_VIEW_COLUMNS as $columnNameSql) {
            $selectClause .= $columnNameSql;
            if ($columnCount != count(self::LIST_VIEW_COLUMNS)) {
                $selectClause .= ",";
            }
            $columnCount++;
        }
        $fromClause = "FROM admins JOIN roles ON admins.role_id = roles.id";
        $orderByClause = "ORDER BY roles.level";
        if ($whereColumnsInfo != null) {
            $this->validateWhereColumns($whereColumnsInfo);
        }

        $q = new SelectBuilder($selectClause, $fromClause, $whereColumnsInfo, $orderByClause);
        return $q->execute();
    }

    // make sure each columnNameSql in columns
    private function validateWhereColumns(array $whereColumnsInfo)
    {
        foreach ($whereColumnsInfo as $columnNameSql => $columnWhereInfo) {
            if (!in_array($columnNameSql, self::LIST_VIEW_COLUMNS)) {
                throw new \Exception("Invalid where column $columnNameSql");
            }
        }
    }

    // returns just the column name ie [id, name, username] instead of [admins.id etc]
    public static function getValidWhereColumnNames(): array
    {
        $names = [];
        foreach (self::LIST_VIEW_COLUMNS as $columnNameSql) {
            $names[] = explode(".", $columnNameSql)[1];
        }

        return $names;
    }

    public static function getColumnNameSqlForColumnName(string $columnName): string
    {
        if (isset(self::LIST_VIEW_COLUMNS[strtolower($columnName)])) {
            return self::LIST_VIEW_COLUMNS[strtolower($columnName)];
        }
        throw new \Exception("undefined column $columnName");
    }
}
