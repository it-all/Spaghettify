<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\FormFormer\Fields\InputField;
use It_All\FormFormer\Fields\SelectField;
use It_All\FormFormer\Fields\SelectOption;
use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Domain\Admin\Admins\Roles\RolesModel;
use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\AdminCrudView;
use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\CrudHelper;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminsView extends AdminCrudView
{
    protected $routePrefix;
    protected $model;
    const SESSION_FILTER_COLUMNS = 'adminsFilterColumnsInfo';
    const SESSION_FILTER_VALUE_KEY = 'adminsFilterField';
    const SESSION_FILTER_FIELD_NAME = 'adminsFilter';

    public function __construct(Container $container)
    {
        parent::__construct($container, new AdminsModel(), ROUTEPREFIX_ADMIN_ADMINS);
    }

    /**
     * override to eliminate some columns
     * @param $request
     * @param $response
     * @param $args
     */
    public function index(Request $request, Response $response, $args)
    {
        $this->indexViewAdmins($response);
    }

    public function indexResetFilter(Request $request, Response $response, $args)
    {
        // redirect to the clean url
        return $this->indexViewAdmins($response, true);
    }

    public function indexViewAdmins(Response $response, bool $resetFilter = false)
    {
        if ($resetFilter) {
            if (isset($_SESSION[self::SESSION_FILTER_COLUMNS])) {
                unset($_SESSION[self::SESSION_FILTER_COLUMNS]);
            }
            if (isset($_SESSION[self::SESSION_FILTER_VALUE_KEY])) {
                unset($_SESSION[self::SESSION_FILTER_VALUE_KEY]);
            }
            // redirect to the clean url
            return $response->withRedirect($this->router->pathFor(ROUTE_ADMIN_ADMINS));
        }

        $whereColumnsInfo = (isset($_SESSION[self::SESSION_FILTER_COLUMNS])) ? $_SESSION[self::SESSION_FILTER_COLUMNS] : null;
        if ($results = pg_fetch_all($this->model->getWithRoles($whereColumnsInfo))) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        // determine where field value
        if (isset($_SESSION[SESSION_REQUEST_INPUT_KEY][self::SESSION_FILTER_FIELD_NAME])) {
            $filterFieldValue = $_SESSION[SESSION_REQUEST_INPUT_KEY][self::SESSION_FILTER_FIELD_NAME];
        } elseif (isset($_SESSION[self::SESSION_FILTER_VALUE_KEY])) {
            $filterFieldValue = $_SESSION[self::SESSION_FILTER_VALUE_KEY];
        } else {
            $filterFieldValue = '';
        }

        $filterErrorMessage = FormHelper::getFieldError(self::SESSION_FILTER_FIELD_NAME);
        FormHelper::unsetSessionVars();

        $insertLink = ($this->authorization->check($this->container->settings['authorization'][getRouteName(true, $this->routePrefix, 'insert')])) ? ['text' => 'Insert '.$this->model->getFormalTableName(false), 'route' => getRouteName(true, $this->routePrefix, 'insert')] : false;

        return $this->view->render(
            $response,
            'admin/adminsList.twig',
            [
                'title' => $this->model->getFormalTableName(),
                'primaryKeyColumn' => $this->model->getPrimaryKeyColumnName(),
                'insertLink' => $insertLink,
                'filterOpsList' => QueryBuilder::getWhereOperatorsText(),
                'filterValue' => $filterFieldValue,
                'filterErrorMessage' => $filterErrorMessage,
                'filterFormAction' => ROUTE_ADMIN_ADMINS,
                'filterFieldName' => self::SESSION_FILTER_FIELD_NAME,
                'isFiltered' => $whereColumnsInfo,
                'resetFilterRoute' => ROUTE_ADMIN_ADMINS_RESET,
                'updatePermitted' => $this->authorization
                    ->check($this->getAuthorizationMinimumLevel('update')),
                'updateRoute' => getRouteName(true, $this->routePrefix, 'update', 'put'),
                'addDeleteColumn' => $this->authorization
                    ->check($this->getAuthorizationMinimumLevel('delete')),
                'deleteRoute' => getRouteName(true, $this->routePrefix, 'delete'),
                'results' => $results,
                'numResults' => $numResults,
                'sortColumn' => 'level',
                'sortByAsc' => $this->model->getDefaultOrderByAsc(),
                'navigationItems' => $this->navigationItems
            ]
        );
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
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($this->model->getColumnByName('name'), null, $nameValue);

        // Username Field
        $usernameValue = (isset($fieldValues['username'])) ? $fieldValues['username'] : '';
        $fields[] = DatabaseTableForm::getFieldFromDatabaseColumn($this->model->getColumnByName('username'), null, $usernameValue);

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
    public function insertView(Request $request, Response $response, $args)
    {
        return $this->view->render(
            $response,
            'admin/form.twig',
            [
                'title' => 'Insert '. $this->model->getFormalTableName(false),
                'form' => $this->getForm($request),
                'navigationItems' => $this->navigationItems
            ]
        );
    }

    /** this can be called for both the initial get and the posted form if errors exist (from controller) */
    public function updateView(Request $request, Response $response, $args)
    {
        // make sure there is a record for the model
        if (!$record = $this->model->selectForPrimaryKey($args['primaryKey'])) {
            return CrudHelper::updateNoRecord($this->container, $response, $args['primaryKey'], $this->model, $this->routePrefix);
        }

        return $this->view->render(
            $response,
            'admin/form.twig',
            [
                'title' => 'Update ' . $this->model->getFormalTableName(false),
                'form' => $this->getForm($request, 'update', (int) $args['primaryKey'], $record),
                'primaryKey' => $args['primaryKey'],
                'navigationItems' => $this->navigationItems
            ]
        );
    }
}
