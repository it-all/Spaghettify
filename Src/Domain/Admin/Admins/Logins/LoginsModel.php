<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Logins;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;

class LoginsModel extends DatabaseTableModel
{
    public function __construct()
    {
        parent::__construct('login_attempts', 'created', false);
    }

    public function insertSuccessfulLogin(int $adminId)
    {
        $this->insert(true,null, $adminId);
    }

    public function insertFailedLogin(string $username = null, int $adminId = null)
    {
        $this->insert(false, $username, $adminId);
    }

    private function insert(bool $success, string $username = null, int $adminId = null)
    {
        // bool must be converted to pg bool format
        $successPg = ($success) ? 't' : 'f';

        $q = new QueryBuilder("INSERT INTO login_attempts (admin_id, username, ip, success, created) VALUES($1, $2, $3, $4, NOW())", $adminId, $username, $_SERVER['REMOTE_ADDR'], $successPg);
        return $q->execute();
    }


}
