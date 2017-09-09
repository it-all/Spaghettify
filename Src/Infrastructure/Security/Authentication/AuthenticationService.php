<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Domain\Admin\Admins\AdminsModel;
use It_All\Spaghettify\Src\Domain\Admin\Admins\Logins\LoginsModel;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;

class AuthenticationService
{
    private $maxFailedLogins;
    private $adminHomeRoutes;

    public function __construct(int $maxFailedLogins, array $adminHomeRoutes)
    {
        $this->maxFailedLogins = $maxFailedLogins;
        $this->adminHomeRoutes = $adminHomeRoutes;
    }

    public function user()
    {
        if (isset($_SESSION['user'])) {
            return $_SESSION['user'];
        }
        return false;
    }

    public function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public function getUserId()
    {
        if (isset($_SESSION['user']['id'])) {
            return $_SESSION['user']['id'];
        }
        return false;
    }

    public function getUserName()
    {
        if (isset($_SESSION['user']['name'])) {
            return $_SESSION['user']['name'];
        }
        return false;
    }

    public function getUserUsername()
    {
        if (isset($_SESSION['user']['username'])) {
            return $_SESSION['user']['username'];
        }
        return false;
    }

    public function getUserRole()
    {
        if (isset($_SESSION['user']['role'])) {
            return $_SESSION['user']['role'];
        }
        return false;
    }

    public function getAdminHomeRouteForUser(): string
    {
        if (!isset($_SESSION['user'])) {
            throw new \Exception("Called for non-logged-in visitor");
        }

        // determine home route: either by username, by role, or default
        if (isset($this->adminHomeRoutes['usernames'][$this->getUserUsername()])) {
            $homeRoute = $this->adminHomeRoutes['usernames'][$this->getUserUsername()];
        } elseif (isset($this->adminHomeRoutes['roles'][$this->getUserRole()])) {
            $homeRoute = $this->adminHomeRoutes['roles'][$this->getUserRole()];
        } else {
            $homeRoute = ROUTE_ADMIN_HOME_DEFAULT;
        }

        return $homeRoute;
    }

    public function attemptLogin(string $username, string $password): bool
    {
        $admins = new AdminsModel();
        $rs = $admins->selectForUsername($username);
        $userRecord = pg_fetch_assoc($rs);

        // check if user exists
        if (!$userRecord) {
            $this->loginFailed($username);
            return false;
        }

        // verify password for that user
        if (password_verify($password, $userRecord['password_hash'])) {
            $this->loginSucceeded($username, $userRecord);
            return true;
        } else {
            $this->loginFailed($username, (int) $userRecord['id']);
            return false;
        }
    }

    private function loginSucceeded(string $username, array $userRecord)
    {
        // set session for user
        $_SESSION['user'] = [
            'id' => $userRecord['id'],
            'name' => $userRecord['name'],
            'username' => $username,
            'role' => $userRecord['role']
        ];
        unset($_SESSION['numFailedLogins']);

        // insert login_attempts record
        (new LoginsModel())->insertSuccessfulLogin($username, (int) $userRecord['id']);
    }

    private function loginFailed(string $username, int $adminId = null)
    {
        if (isset($_SESSION['numFailedLogins'])) {
            $_SESSION['numFailedLogins']++;
        } else {
            $_SESSION['numFailedLogins'] = 1;
        }

        // insert login_attempts record
        (new LoginsModel())->insertFailedLogin($username, $adminId);
    }

    public function tooManyFailedLogins(): bool
    {
        return isset($_SESSION['numFailedLogins']) &&
            $_SESSION['numFailedLogins'] > $this->maxFailedLogins;
    }

    public function getNumFailedLogins(): int
    {
        return (isset($_SESSION['numFailedLogins'])) ? $_SESSION['numFailedLogins'] : 0;
    }

    public function logout()
    {
        unset($_SESSION['user']);
    }

    public function getLoginFields(): array
    {
        return ['username', 'password_hash'];
    }

    /** note there should be different validation rules for logging in than creating users.
     * ie no minlength or character rules on password here
     */
    public function getLoginFieldValidationRules(): array
    {
        return [
            'required' => [['username'], ['password_hash']]
        ];
    }

    public function getForm(string $csrfNameKey, string $csrfNameValue, string $csrfValueKey, string $csrfValueValue, string $action)
    {
        $adminsModel = new AdminsModel();

        $fields = [];
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($adminsModel->getColumnByName('username'));
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($adminsModel->getColumnByName('password_hash'), null, null, 'Password', 'password');
        $fields[] = FormHelper::getCsrfNameField($csrfNameKey, $csrfNameValue);
        $fields[] = FormHelper::getCsrfValueField($csrfValueKey, $csrfValueValue);
        $fields[] = FormHelper::getSubmitField();

        return new Form($fields, ['method' => 'post', 'action' => $action, 'novalidate' => 'novalidate'], FormHelper::getGeneralError());
    }
}
