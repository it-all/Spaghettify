<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authorization;

use It_All\Spaghettify\Src\Domain\Admin\Admins\Roles\RolesModel;
use Psr\Log\InvalidArgumentException;

class AuthorizationService
{
    private $roles;
    private $functionalityMinimumPermissions;

    public function __construct(array $functionalityMinimumPermissions = [])
    {
        $this->functionalityMinimumPermissions = $functionalityMinimumPermissions;
        $rolesModel = new RolesModel();
        $this->roles = $rolesModel->getRoles();
    }

    // $functionality like 'marketing' or 'marketing.index'
    public function getMinimumPermission(string $functionality)
    {
        if (!isset($this->functionalityMinimumPermissions[$functionality])) {

            // no exact match, so see if there are multiple terms and first term matches
            $fParts = explode('.', $functionality);
            if (count($fParts) > 1 && isset($this->functionalityMinimumPermissions[getRouteName(true, $fParts[0])])) {
                return $this->functionalityMinimumPermissions[getRouteName(true, $fParts[0])];
            }

            throw new InvalidArgumentException('Not found in functionalityMinimumPermissions: '.$functionality);
        }

        return $this->functionalityMinimumPermissions[$functionality];
    }

    /**
     * @param string $minimumPermission
     * @return bool
     */
    public function check(string $minimumPermission = 'owner'): bool
    {
        if (!in_array($minimumPermission, $this->roles)) {
            throw new InvalidArgumentException("minimumRole $minimumPermission must be a valid role");
        }
        if (!isset($_SESSION['user']['role'])) {
            return false;
        }

        $role = $_SESSION['user']['role'];

        if (!in_array($role, $this->roles)) {
            unset($_SESSION['user']); // force logout
            return false;
        }

        if (array_search($role, $this->roles) <= array_search($minimumPermission, $this->roles)) {

            return true;
        }

        return false;
    }

    public function checkFunctionality(string $functionality): bool
    {
        return $this->check($this->getMinimumPermission($functionality));
    }
}
