<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\SelectBuilder;
use It_All\Spaghettify\Src\Infrastructure\ListViewModel;

class AdminsModel extends ListViewModel
{
    const TABLE_NAME = 'admins';

    const LIST_VIEW_COLUMNS = [
        'id' => 'admins.id',
        'name' => 'admins.name',
        'username' => 'admins.username',
        'role' => 'roles.role',
        'level' => 'roles.level'
    ];

    public function __construct()
    {
        parent::__construct(new DatabaseTableModel(self::TABLE_NAME), self::LIST_VIEW_COLUMNS);
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
            throw new \Exception("Invalid Primary Key $primaryKeyValue for ".self::TABLE_NAME);
        }

        $changedColumns = $this->getChangedColumns($record, $name, $username, $roleId, password_hash($password, PASSWORD_DEFAULT));
        return $this->getPrimaryTableModel()->updateRecordByPrimaryKey($changedColumns, $primaryKeyValue);
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

    public function getListView(array $filterColumnsInfo = null)
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
        if ($filterColumnsInfo != null) {
            $this->validateFilterColumns($filterColumnsInfo);
        }

        $q = new SelectBuilder($selectClause, $fromClause, $filterColumnsInfo, $orderByClause);
        return $q->execute();
    }

}
