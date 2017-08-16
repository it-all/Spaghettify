<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Domain\Admin\Admins\AdminsModel;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;

class AuthenticationService
{
    private $maxFailedLogins;

    public function __construct(int $maxFailedLogins)
    {
        $this->maxFailedLogins = $maxFailedLogins;
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

    public function attemptLogin(string $username, string $password): bool
    {
        $admins = new AdminsModel();
        $rs = $admins->selectForUsername($username);
        $userRecord = pg_fetch_assoc($rs);

        // check if user exists
        if (!$userRecord) {
            $this->loginFailed();
            return false;
        }

        // verify password for that user
        if (password_verify($password, $userRecord['password_hash'])) {
            $this->loginSucceeded($username, $userRecord);
            return true;
        } else {
            $this->loginFailed();
            return false;
        }
    }

    private function loginSucceeded(string $username, array $userRecord)
    {
        // set session for user
        $_SESSION['user'] = [
            'id' => $userRecord['id'],
            'username' => $username,
            'role' => $userRecord['role']
        ];
        unset($_SESSION['numFailedLogins']);
    }

    private function loginFailed()
    {
        if (isset($_SESSION['numFailedLogins'])) {
            $_SESSION['numFailedLogins']++;
        } else {
            $_SESSION['numFailedLogins'] = 1;
        }
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

    /** note there should be different validation rules for logging in than creating users.
     * ie no minlength or character rules on password here
     */
    public function getLoginFieldValidationRules(): array
    {
        return [
            'username' => [
                'required' => true
            ],
            'password_hash' => [
                'required' => true
            ],
        ];
    }

    public function getForm(string $csrfNameKey, string $csrfNameValue, string $csrfValueKey, string $csrfValueValue, string $action)
    {
        $adminsModel = new AdminsModel();
        $validation = $this->getLoginFieldValidationRules();

        $fields = [];
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($adminsModel->getColumnByName('username'), $validation['username']);
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($adminsModel->getColumnByName('password_hash'), $validation['password_hash'], 'Password');
        $fields[] = FormHelper::getCsrfNameField($csrfNameKey, $csrfNameValue);
        $fields[] = FormHelper::getCsrfValueField($csrfValueKey, $csrfValueValue);
        $fields[] = FormHelper::getSubmitField();

        return new Form($fields, ['method' => 'post', 'action' => $action], FormHelper::getGeneralError());
    }
}
