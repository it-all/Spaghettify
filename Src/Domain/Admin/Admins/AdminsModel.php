<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\SelectBuilder;

class AdminsModel extends DatabaseTableModel
{
//    private $roles; // array of existing roles

    const COLUMNS_WITH_ROLES = ['admins.id', 'admins.name', 'admins.username', 'roles.role', 'roles.level'];

    public function __construct()
    {
        parent::__construct('admins');
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
        return $this->updateRecordByPrimaryKey($changedColumns, $primaryKeyValue);
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

        return parent::hasRecordChanged($fieldValues, $primaryKeyValue, $skipColumns);
    }

    public function getWithRoles(array $whereColumnsInfo = null)
    {
        $selectClause = "SELECT ";
        $columnCount = 1;
        foreach (self::COLUMNS_WITH_ROLES as $columnNameSql) {
            $selectClause .= $columnNameSql;
            if ($columnCount != count(self::COLUMNS_WITH_ROLES)) {
                $selectClause .= ",";
            }
            $columnCount++;
        }
        $fromClause = "FROM admins JOIN roles ON admins.role_id = roles.id";
        $orderByClause = "ORDER BY roles.level";
        if ($whereColumnsInfo != null) {
            $this->validateWhereColumnsWithRoles($whereColumnsInfo);
        }

        $q = new SelectBuilder($selectClause, $fromClause, $whereColumnsInfo, $orderByClause);
        return $q->execute();
    }

    // make sure each columnNameSql in columns
    private function validateWhereColumnsWithRoles(array $whereColumnsInfo)
    {
        foreach ($whereColumnsInfo as $columnNameSql => $columnWhereInfo) {
            if (!in_array($columnNameSql, self::COLUMNS_WITH_ROLES)) {
                throw new \Exception("Invalid where column $columnNameSql");
            }
        }
    }

    // returns just the column name ie [id, name, username] instead of [admins.id etc]
    public static function getValidWhereColumnNames(): array
    {
        $names = [];
        foreach (self::COLUMNS_WITH_ROLES as $columnNameSql) {
            $names[] = explode(".", $columnNameSql)[1];
        }

        return $names;
    }

    public static function getColumnNameSqlForColumnName(string $columnName): string
    {
        switch (strtolower($columnName)) {
            case 'id':
                return 'admins.id';
                break;
            case 'name':
                return 'admins.name';
                break;
            case 'username':
                return 'admins.username';
                break;
            case 'role':
                return 'roles.role';
                break;
            case 'level':
                return 'roles.level';
                break;
            default:
                throw new \Exception("$columnName not found");
        }
    }


    public function getWithRolesOld(string $whereId = null, string $whereIdOperator = null, string $whereName = null, string $whereNameOperator = null, string $whereUsername = null, string $whereUsernameOperator = null, string $whereRole = null, string $whereRoleOperator = null, string $whereLevel = null, string $whereLevelOperator = null)
    {
        $selectClause = "SELECT a.id, a.name, a.username, r.role, r.level";
        $fromClause = "FROM admins a JOIN roles r ON a.role_id = r.id";
        $whereColumns = [
            'id' => [
                'tableAbbrev' => 'a',
                'whereValue' => $whereId,
                'whereOperator' => $whereIdOperator
            ],
            'name' => [
                'tableAbbrev' => 'a',
                'whereValue' => $whereName,
                'whereOperator' => $whereNameOperator
            ],
            'username' => [
                'tableAbbrev' => 'a',
                'whereValue' => $whereUsername,
                'whereOperator' => $whereUsernameOperator
            ],
            'role' => [
                'tableAbbrev' => 'r',
                'whereValue' => $whereRole,
                'whereOperator' => $whereRoleOperator
            ],
            'level' => [
                'tableAbbrev' => 'r',
                'whereValue' => $whereLevel,
                'whereOperator' => $whereLevelOperator
            ]
        ];
        $orderByClause = "ORDER BY r.level";

        $q = new SelectBuilder($selectClause, $fromClause, $whereColumns, $orderByClause);
        return $q->execute();
    }
}
