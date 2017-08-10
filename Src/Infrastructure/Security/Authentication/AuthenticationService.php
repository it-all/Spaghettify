<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\FormFormer\Fields\InputField;
use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Domain\Admin\Admins\AdminsModel;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use It_All\Spaghettify\Src\Infrastructure\Utilities\ValidationService;

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
        $admins = new AdminsModel('admins');
        $rs = $admins->selectForUsername($username);
        $user = pg_fetch_assoc($rs);

        // check if user exists
        if (!$user) {
            $this->failedLogin();
            return false;
        }

        // verify password for that user
        if (password_verify($password, $user['password_hash'])) {
            // set session for user
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $username,
                'role' => $user['role']
            ];
            return true;
        } else {
            $this->failedLogin();
            return false;
        }
    }

    private function failedLogin()
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
        $adminsModel = new AdminsModel();

        $usernameColumn = $adminsModel->getColumnByName('username');

        return [
            'username' => [
                'required' => true,
                'alpha' => true,
                'maxlength' => $usernameColumn->getCharacterMaximumLength(),
                'minlength' => '4'
            ],
            'password_hash' => [
                'required' => true
            ]
        ];
    }

    public function getForm(string $csrfNameKey, string $csrfNameValue, string $csrfValueKey, string $csrfValueValue, string $action)
    {
        $adminsModel = new AdminsModel();

        $fields = [];
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($adminsModel->getColumnByName('username'), true);
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($adminsModel->getColumnByName('password_hash'), true, 'Password');
        $fields[] = FormHelper::getCsrfNameField($csrfNameKey, $csrfNameValue);
        $fields[] = FormHelper::getCsrfValueField($csrfValueKey, $csrfValueValue);
        $fields[] = FormHelper::getSubmitField();

        return new Form($fields, ['method' => 'post', 'action' => $action, 'novalidate' => 'novalidate'], FormHelper::getGeneralError());
    }

    public function getLoginFields(): array
    {
        $adminsModel = new AdminsModel();

        $loginFields = [];

        $fieldColumns = [
            'username' => [
                'labelOverride' => null,
                'nameOverride' => null,
                'inputOverride' => null,
                'persist' => true
            ],
            'password_hash' => [
                'labelOverride' => 'Password',
                'nameOverride' => 'password',
                'inputOverride' => 'password',
                'persist' => false
            ]
        ];

        foreach ($fieldColumns as $columnName => $fieldInfo) {
            $fieldName = ($fieldInfo['nameOverride']) ?: $columnName;
            $loginFields[$fieldName] = DatabaseTableForm::getFieldFromDatabaseColumn(
                $adminsModel->getColumnByName($columnName),
                $fieldInfo['labelOverride'],
                $fieldInfo['inputOverride'],
                null,
                $fieldInfo['nameOverride'],
                $fieldInfo['nameOverride'],
                $fieldInfo['persist']
            );
        }

        $loginFields['submit'] = DatabaseTableForm::getSubmitField();

        return $loginFields;
    }

    public function getFocusField()
    {
        if (isset($_SESSION['validationErrors']) && !isset($_SESSION['validationErrors']['username'])) {
            return 'password';
        } elseif (isset($_SESSION['generalFormError'])) {
            return ''; // no focus field set
        }
        return 'username';
    }

    public function getLoginFieldsValidationRules(): array
    {
        return ValidationService::getRules($this->getLoginFields());
    }
}
