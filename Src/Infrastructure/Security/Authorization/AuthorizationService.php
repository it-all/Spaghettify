<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authorization;

use It_All\Spaghettify\Src\Domain\Admin\Administrators\Roles\RolesModel;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;

class AuthorizationService
{
    private $functionalityMinimumPermissions;
    private $roles;
    private $baseRole;

    public function __construct(array $functionalityMinimumPermissions = [])
    {
        $this->functionalityMinimumPermissions = $functionalityMinimumPermissions;
        $rolesModel = new RolesModel();
        $this->roles = $rolesModel->getRoles();
        $this->baseRole = $rolesModel->getBaseRole();
    }

    // $functionality like 'marketing' or 'marketing.index'
    // if not found as an exact match or category match, the base (least permission) role is returned
    public function getMinimumPermission(string $functionality): string
    {
        if (!isset($this->functionalityMinimumPermissions[$functionality])) {

            // no exact match, so see if there are multiple terms and first term matches
            $fParts = explode('.', $functionality);
            if (count($fParts) > 1 && isset($this->functionalityMinimumPermissions[getRouteName(true, $fParts[0])])) {
                return $this->functionalityMinimumPermissions[getRouteName(true, $fParts[0])];
            }

            // no matches
            return $this->baseRole;
        }

        return $this->functionalityMinimumPermissions[$functionality];
    }

    public function check(string $minimumPermission = 'owner'): bool
    {
        if (!in_array($minimumPermission, $this->roles)) {
            throw new \Exception("minimumRole $minimumPermission must be a valid role");
        }
        if (!isset($_SESSION[SESSION_USER][SESSION_USER_ROLE])) {
            return false;
        }

        $role = $_SESSION[SESSION_USER][SESSION_USER_ROLE];

        if (!in_array($role, $this->roles)) {
            // database this event
            unset($_SESSION[SESSION_USER]); // force logout
            return false;
        }

        if (array_search($role, $this->roles) <= array_search($minimumPermission, $this->roles)) {

            return true;
        }

        return false;
    }

    // note, returns false if the minimum permission for $functionality is not defined
    public function checkFunctionality(string $functionality): bool
    {
        if (!$p = $this->getMinimumPermission($functionality)) {
            return false;
        }
        return $this->check($p);
    }
}
