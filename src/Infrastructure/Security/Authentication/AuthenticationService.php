<?php
declare(strict_types=1);

namespace Infrastructure\Security\Authentication;

use It_All\FormFormer\Form;
use Domain\Admin\Administrators\AdministratorsModel;
use Domain\Admin\Administrators\Logins\LoginsModel;
use Infrastructure\UserInterface\Forms\DatabaseTableForm;
use Infrastructure\UserInterface\Forms\FormHelper;

class AuthenticationService
{
    private $maxFailedLogins;
    private $adminHomeRoutes;

    public function __construct(int $maxFailedLogins, array $adminHomeRoutes)
    {
        $this->maxFailedLogins = $maxFailedLogins;
        $this->adminHomeRoutes = $adminHomeRoutes;
    }

    public function user(): ?array
    {
        if (isset($_SESSION[SESSION_USER])) {
            return $_SESSION[SESSION_USER];
        }
        return null;
    }

    public function check(): bool
    {
        return isset($_SESSION[SESSION_USER]);
    }

    public function getUserId(): ?int
    {
        if (isset($_SESSION[SESSION_USER][SESSION_USER_ID])) {
            return (int) $_SESSION[SESSION_USER][SESSION_USER_ID];
        }
        return null;
    }

    public function getUserName(): ?string
    {
        if (isset($_SESSION[SESSION_USER][SESSION_USER_NAME])) {
            return $_SESSION[SESSION_USER][SESSION_USER_NAME];
        }
        return null;
    }

    public function getUserUsername(): ?string
    {
        if (isset($_SESSION[SESSION_USER][SESSION_USER_USERNAME])) {
            return $_SESSION[SESSION_USER][SESSION_USER_USERNAME];
        }
        return null;
    }

    public function getUserRole(): ?string
    {
        if (isset($_SESSION[SESSION_USER][SESSION_USER_ROLE])) {
            return $_SESSION[SESSION_USER][SESSION_USER_ROLE];
        }
        return null;
    }

    public function getAdminHomeRouteForUser(): string
    {
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
        $administrators = new AdministratorsModel();
        $rs = $administrators->selectForUsername($username);
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
        $_SESSION[SESSION_USER] = [
            SESSION_USER_ID => $userRecord['id'],
            SESSION_USER_NAME => $userRecord['name'],
            SESSION_USER_USERNAME => $username,
            SESSION_USER_ROLE => $userRecord['role']
        ];
        unset($_SESSION[SESSION_NUMBER_FAILED_LOGINS]);

        // insert login_attempts record
        (new LoginsModel())->insertSuccessfulLogin($username, (int) $userRecord['id']);
    }

    private function loginFailed(string $username, int $adminId = null)
    {
        if (isset($_SESSION[SESSION_NUMBER_FAILED_LOGINS])) {
            $_SESSION[SESSION_NUMBER_FAILED_LOGINS]++;
        } else {
            $_SESSION[SESSION_NUMBER_FAILED_LOGINS] = 1;
        }

        // insert login_attempts record
        (new LoginsModel())->insertFailedLogin($username, $adminId);
    }

    public function tooManyFailedLogins(): bool
    {
        return isset($_SESSION[SESSION_NUMBER_FAILED_LOGINS]) &&
            $_SESSION[SESSION_NUMBER_FAILED_LOGINS] > $this->maxFailedLogins;
    }

    public function getNumFailedLogins(): int
    {
        return (isset($_SESSION[SESSION_NUMBER_FAILED_LOGINS])) ? $_SESSION[SESSION_NUMBER_FAILED_LOGINS] : 0;
    }

    public function logout()
    {
        unset($_SESSION[SESSION_USER]);
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
        $administratorsModel = new AdministratorsModel();

        $fields = [];
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($administratorsModel->getColumnByName('username'));
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($administratorsModel->getColumnByName('password_hash'), null, null, 'Password', 'password');
        $fields[] = FormHelper::getCsrfNameField($csrfNameKey, $csrfNameValue);
        $fields[] = FormHelper::getCsrfValueField($csrfValueKey, $csrfValueValue);
        $fields[] = FormHelper::getSubmitField();

        return new Form($fields, ['method' => 'post', 'action' => $action, 'novalidate' => 'novalidate'], FormHelper::getGeneralError());
    }
}
