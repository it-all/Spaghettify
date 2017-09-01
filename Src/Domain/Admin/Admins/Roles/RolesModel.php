<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Roles;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use Slim\Container;

class RolesModel extends DatabaseTableModel
{
    private $defaultRoleId;

    public function __construct()
    {
        parent::__construct('roles');
        $this->addColumnNameConstraint('level', 'positive');
        $this->defaultOrderByColumnName = 'level';
    }

    /** pass in $defaultRole in order to set $defaultRoleId */
    public function getRoles(string $defaultRole = null): array
    {
        $roles = [];
        $rs = $this->select('id, role', 'level');
        while ($row = pg_fetch_array($rs)) {
            $roles[$row['id']] = $row['role'];
            if ($defaultRole != null && $row['role'] == $defaultRole) {
                $this->defaultRoleId = $row['id'];
            }
        }
        return $roles;
    }

    public function getDefaultRoleId(string $defaultRole)
    {
        if (isset($this->defaultRoleId)) {
            return $this->defaultRoleId;
        }
        $q = new QueryBuilder("SELECT id FROM roles WHERE role = $1", $defaultRole);
        return $q->getOne();
    }
}
