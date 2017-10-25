<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\FormFormer\Fields\InputField;
use It_All\FormFormer\Fields\SelectField;
use It_All\FormFormer\Fields\SelectOption;
use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Domain\Admin\Admins\Roles\RolesModel;
use It_All\Spaghettify\Src\Infrastructure\Database\SingleTable\SingleTableHelper;
use It_All\Spaghettify\Src\Infrastructure\ListView;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminsView extends ListView
{
    protected $routePrefix;
    protected $adminsModel;

    public function __construct(Container $container)
    {
        $this->routePrefix = ROUTEPREFIX_ADMIN_ADMINS;
        $this->adminsModel = new AdminsModel();

        parent::__construct($container, 'admins', ROUTE_ADMIN_ADMINS, $this->adminsModel, ROUTE_ADMIN_ADMINS_RESET, 'admin/lists/adminsList.twig');

        $insertLink = ($this->authorization->check($this->container->settings['authorization'][getRouteName(true, $this->routePrefix, 'insert')])) ? ['text' => 'Insert '.$this->adminsModel->getPrimaryTableName(false), 'route' => getRouteName(true, $this->routePrefix, 'insert')] : false;
        $this->setInsert($insertLink);

        $this->setUpdate($this->authorization->check($this->getAuthorizationMinimumLevel('update')), $this->adminsModel->getUpdateColumnName(), getRouteName(true, $this->routePrefix, 'update', 'put'));

        $this->setDelete($this->container->authorization->check($this->getAuthorizationMinimumLevel('delete')), getRouteName(true, $this->routePrefix, 'delete'));
    }

    private function pwFieldsHaveError(): bool
    {
        return strlen(FormHelper::getFieldError('password')) > 0 || strlen(FormHelper::getFieldError('password_confirm')) > 0;
    }

    private function getForm(Request $request, string $action = 'insert', int $primaryKey = null,  array $record = null)
    {
        if ($action != 'insert' && $action != 'update') {
            throw new \Exception("Invalid action $action");
        }

        $fields = [];

        if ($action == 'insert') {
            $fieldValues = ($request->isGet()) ? [] : $_SESSION[SESSION_REQUEST_INPUT_KEY];
            $formAction = $this->router->pathFor(getRouteName(true, $this->routePrefix, 'insert', 'post'));
            $passwordLabel = 'Password';
        } else {
            $fieldValues = ($request->isGet()) ? $record : $_SESSION[SESSION_REQUEST_INPUT_KEY];
            $formAction = $this->router->pathFor(getRouteName(true, $this->routePrefix, 'update', 'put'), ['primaryKey' => $primaryKey]);
            $passwordLabel = 'Password [leave blank to keep existing]';
            $fields[] = FormHelper::getPutMethodField();
        }

        // Name Field
        $nameValue = (isset($fieldValues['name'])) ? $fieldValues['name'] : '';
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($this->adminsModel->getPrimaryTableModel()->getColumnByName('name'), null, $nameValue);

        // Username Field
        $usernameValue = (isset($fieldValues['username'])) ? $fieldValues['username'] : '';
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($this->adminsModel->getPrimaryTableModel()->getColumnByName('username'), null, $usernameValue);

        // Password Fields
        // determine values of pw and pw conf fields
        // values will persist if no errors in either field
        if ($request->isGet()) {
            $passwordValue = '';
            $passwordConfirmationValue = '';
        } else {
            $passwordValue = ($this->pwFieldsHaveError()) ? '' : $fieldValues['password'];
            $passwordConfirmationValue = ($this->pwFieldsHaveError()) ? '' : $fieldValues['password_confirm'];
        }

        $fields[] = new InputField($passwordLabel, ['name' => 'password', 'id' => 'password', 'type' => 'password', 'required' => 'required', 'value' => $passwordValue], FormHelper::getFieldError('password'));

        $fields[] = new InputField('Confirm Password', ['name' => 'password_confirm', 'id' => 'password_confirm', 'type' => 'password', 'required' => 'required', 'value' => $passwordConfirmationValue], FormHelper::getFieldError('password_confirm'));

        // Role Field
        $rolesOptions = [];
        $rolesModel = new RolesModel();
        foreach ($rolesModel->getRoles() as $roleId => $role) {
            $rolesOptions[] = new SelectOption($role, (string) $roleId);
        }

        $selectedOptionValue = (isset($fieldValues['role_id'])) ? $fieldValues['role_id'] : (string) $rolesModel->getDefaultRoleId($this->container->settings['adminDefaultRole']);
        $fields[] = new SelectField($rolesOptions, $selectedOptionValue, 'Role', ['name' => 'role_id', 'id' => 'role_id', 'required' => 'required'], FormHelper::getFieldError('role_id'));

        // CSRF Fields
        $fields[] = FormHelper::getCsrfNameField($this->csrf->getTokenNameKey(), $this->csrf->getTokenName());
        $fields[] = FormHelper::getCsrfValueField($this->csrf->getTokenValueKey(), $this->csrf->getTokenValue());

        // Submit Field
        $fields[] = FormHelper::getSubmitField();

        $form = new Form($fields, ['method' => 'post', 'action' => $formAction, 'novalidate' => 'novalidate'], FormHelper::getGeneralError());
        FormHelper::unsetSessionVars();

        return $form;
    }

    /** this can be called for both the initial get and the posted form if errors exist (from controller) */
    public function getInsert(Request $request, Response $response, $args)
    {
        return $this->view->render(
            $response,
            'admin/form.twig',
            [
                'title' => 'Insert '. $this->adminsModel->getPrimaryTableName(false),
                'form' => $this->getForm($request),
                'navigationItems' => $this->navigationItems
            ]
        );
    }

    public function getUpdate(Request $request, Response $response, $args)
    {
        return $this->updateView($request, $response, $args);
    }

    /** this can be called for both the initial get and the posted form if errors exist (from controller) */
    public function updateView(Request $request, Response $response, $args)
    {
        // make sure there is a record for the model
        if (!$record = $this->adminsModel->getPrimaryTableModel()->selectForPrimaryKey($args['primaryKey'])) {
            return SingleTableHelper::updateNoRecord($this->container, $response, $args['primaryKey'], $this->adminsModel->getPrimaryTableModel(), $this->routePrefix);
        }

        return $this->view->render(
            $response,
            'admin/form.twig',
            [
                'title' => 'Update ' . $this->adminsModel->getPrimaryTableModel()->getFormalTableName(false),
                'form' => $this->getForm($request, 'update', (int) $args['primaryKey'], $record),
                'primaryKey' => $args['primaryKey'],
                'navigationItems' => $this->navigationItems
            ]
        );
    }
}
