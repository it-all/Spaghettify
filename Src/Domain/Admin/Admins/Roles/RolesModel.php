<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Roles;

use It_All\Spaghettify\Src\Infrastructure\Database\SingleTable\SingleTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;

// note that level 1 is the greatest permission
class RolesModel extends SingleTableModel
{
    private $defaultRoleId;
    private $roles;
    private $baseRoleId;

    public function __construct()
    {
        parent::__construct('roles', 'id, role, level','level');
        $this->addColumnNameConstraint('level', 'positive');
    }

    public function setRoles(string $defaultRole = null)
    {
        $this->roles = [];
        $rs = $this->select();
        $lastRoleId = '';
        while ($row = pg_fetch_array($rs)) {
            $this->roles[$row['id']] = $row['role'];

            if ($defaultRole != null && $row['role'] == $defaultRole) {
                $this->defaultRoleId = $row['id'];
            }

            $lastRoleId = $row['id'];
        }

        // the last role returned is set to baseRole since order by level
        $this->baseRoleId = $lastRoleId;
    }

    /** pass in $defaultRole in order to set $defaultRoleId */
    public function getRoles(string $defaultRole = null): array
    {
        if (!isset($this->roles)) {
            $this->setRoles($defaultRole);
        }

        return $this->roles;
    }

    public function getBaseRoleId(): string
    {
        if (!isset($this->roles)) {
            $this->setRoles();
        }

        return $this->baseRoleId;
    }

    public function getBaseRole()
    {
        if (!isset($this->roles)) {
            $this->setRoles();
        }

        return $this->roles[$this->baseRoleId];
    }

    public function getDefaultRoleId(string $defaultRole)
    {
        if (isset($this->defaultRoleId)) {
            return $this->defaultRoleId;
        }
        $q = new QueryBuilder("SELECT id FROM roles WHERE role = $1", $defaultRole);
        return $q->getOne();
    }

    public static function hasAdmin(int $roleId): bool
    {
        $q = new QueryBuilder("SELECT COUNT(id) FROM admins WHERE role_id = $1", $roleId);
        return (bool) $q->getOne();
    }
}
