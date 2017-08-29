<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Roles;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;

class RolesModel extends DatabaseTableModel
{
    public function __construct()
    {
        parent::__construct('roles');
        $this->addColumnNameConstraint('level', 'positive');
        $this->defaultOrderByColumnName = 'level';
    }

    public function getRoles(): array
    {
        $roles = [];
        $rs = $this->select('role', 'level');
        while ($row = pg_fetch_array($rs)) {
            $roles[] = $row['role'];
        }
        return $roles;
    }
}
