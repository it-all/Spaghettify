<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;

class AdminsModel extends DatabaseTableModel
{
//    private $roles; // array of existing roles

    public function __construct()
    {
        parent::__construct('admins');
    }

    public function insert(string $name, string $username, string $password, int $roleId)
    {
        $q = new QueryBuilder("INSERT INTO admins (name, username, password_hash, role_id) VALUES($1, $2, $3, $4)", $name, $username, password_hash($password, PASSWORD_DEFAULT), $roleId);
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

        $changedColumns = $this->getChangedColumns($record, $name, $username, $roleId, $password);
        return $this->updateRecordByPrimaryKey($changedColumns, $primaryKeyValue);

//
//        $q = new QueryBuilder("UPDATE admins SET name = $1, username = $2", $columnValues['name'], $columnValues['username']);
//        $argNum = 3;
//        if (strlen($columnValues['password_hash']) > 0) {
//            $q->add(", password_hash = $$argNum", password_hash($columnValues['password_hash'], PASSWORD_DEFAULT));
//            $argNum++;
//        }
//        $q->add(" WHERE id = $$argNum RETURNING id", $primaryKeyValue);
//        return $q->execute();
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
}
