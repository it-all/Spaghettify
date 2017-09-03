<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\FormFormer\Fields\InputField;
use It_All\FormFormer\Fields\SelectField;
use It_All\FormFormer\Fields\SelectOption;
use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Domain\Admin\Admins\Roles\RolesModel;
use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\AdminCrudView;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminsView extends AdminCrudView
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new AdminsModel(), 'admins');
    }

    /**
     * override to eliminate some columns
     * @param $request
     * @param $response
     * @param $args
     */
    public function index(Request $request, Response $response, $args)
    {
        $this->indexView($response, 'id, name, username');
    }

    public function indexView(Response $response, string $columns = '*')
    {
        if ($results = pg_fetch_all($this->model->getWithRoles())) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        $insertLink = ($this->authorization->check($this->container->settings['authorization'][$this->routePrefix.'.insert'])) ? ['text' => 'Insert '.$this->model->getFormalTableName(false), 'route' => $this->routePrefix.'.insert'] : false;

        return $this->view->render(
            $response,
            'admin/list.twig',
            [
                'title' => $this->model->getFormalTableName(),
                'primaryKeyColumn' => $this->model->getPrimaryKeyColumnName(),
                'insertLink' => $insertLink,
                'updatePermitted' => $this->authorization
                    ->check($this->container->settings['authorization'][$this->routePrefix.'.update']),
                'updateRoute' => $this->routePrefix.'.put.update',
                'addDeleteColumn' => true,
                'deleteRoute' => $this->routePrefix.'.delete',
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
            $formAction = $this->router->pathFor($this->routePrefix.'.post.insert');
            $passwordLabel = 'Password';
        } else {
            $fieldValues = ($request->isGet()) ? $record : $_SESSION[SESSION_REQUEST_INPUT_KEY];
            $formAction = $this->router->pathFor($this->routePrefix.'.put.update', ['primaryKey' => $primaryKey]);
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
            $_SESSION['adminNotice'] = [
                "Record ".$args['primaryKey']." Not Found",
                'adminNoticeFailure'
            ];
            return $response->withRedirect($this->router->pathFor($this->routePrefix.'.index'));
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


//    /**
//     * override to leave pw field blank
//     * @param $request
//     * @param $response
//     * @param $args
//     * @return mixed
//     */
//    public function getUpdate(Request $request, Response $response, $args)
//    {
//        // make sure there is a record for the model
//        if (!$record = $this->model->selectForPrimaryKey($args['primaryKey'])) {
//            $_SESSION['adminNotice'] = [
//                "Record ".$args['primaryKey']." Not Found",
//                'adminNoticeFailure'
//            ];
//            return $response->withRedirect($this->router->pathFor($this->routePrefix.'.index'));
//        }
//
//        $record['password_hash'] = '';
//
//        /**
//         * data to send to FormHelper - either from the model or from prior input. Note that when sending null FormHelper defaults to using $_SESSION[SESSION_REQUEST_INPUT_KEY]. It's important to send null, not $_SESSION['formInput'], because FormHelper unsets $_SESSION[SESSION_REQUEST_INPUT_KEY] after using it.
//         * note, this works for post/put because controller calls this method directly in case of errors instead of redirecting
//         */
//        $fieldData = ($request->isGet()) ? $record : null;
//
//        return $this->updateView($request, $response, $args, $fieldData);
//    }
}
