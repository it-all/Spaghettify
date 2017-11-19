<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Administrators;

use It_All\Spaghettify\Src\Infrastructure\Controller;
use It_All\Spaghettify\Src\Infrastructure\Database\SingleTable\SingleTableController;
use It_All\Spaghettify\Src\Infrastructure\Database\SingleTable\SingleTableHelper;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AdministratorsController extends Controller
{
    private $administratorsModel;
    private $view;
    private $routePrefix;
    private $administratorsSingleTableController;

    public function __construct(Container $container)
    {
        $this->administratorsModel = new AdministratorsModel();
        $this->view = new AdministratorsView($container);
        $this->routePrefix = ROUTEPREFIX_ADMIN_ADMINISTRATORS;
        $this->administratorsSingleTableController = new SingleTableController($container, $this->administratorsModel->getPrimaryTableModel(), $this->view, $this->routePrefix);
        parent::__construct($container);
    }

    private function setValidation(array $record = null)
    {
        $input = $_SESSION[SESSION_REQUEST_INPUT_KEY];
        $this->validator = $this->validator->withData($input);

        $this->validator->rule('required', ['name', 'username', 'role_id']);
        $this->validator->rule('regex', 'name', '%^[a-zA-Z\s]+$%')->message('must be letters and spaces only');
        $this->validator->rule('lengthMin', 'username', 4);
        if ($record != null && strlen($input['password']) > 0) {
            $this->validator->rule('required', ['password', 'password_confirm']);
            $this->validator->rule('lengthMin', 'password', 12);
            $this->validator->rule('equals', 'password', 'password_confirm')->message('must be the same as Confirm Password');
        }

        // unique column rule for username if it has changed
        if ($record == null || $record['username'] != $input['username']) {
            $this->validator::addRule('unique', function($field, $value, array $params = [], array $fields = []) {
                if (!$params[1]->errors($field)) {
                    return !$params[0]->recordExistsForValue($value);
                }
                return true; // skip validation if there is already an error for the field
            }, 'Already exists.');

            $this->validator->rule('unique', 'username', $this->administratorsModel->getPrimaryTableModel()->getColumnByName('username'), $this->validator);
        }
    }

    public function postIndexFilter(Request $request, Response $response, $args)
    {
        return $this->setIndexFilter($request, $response, $args, $this->administratorsModel::SELECT_COLUMNS, ROUTE_ADMIN_ADMINISTRATORS, $this->view);
    }

    public function postInsert(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality(getRouteName(true, $this->routePrefix, 'insert'))) {
            throw new \Exception('No permission.');
        }

        $this->setRequestInput($request);
        // no boolean fields to add

        $this->setValidation();

        if (!$this->validator->validate()) {
            // redisplay the form with input values and error(s)
            FormHelper::setFieldErrors($this->validator->getFirstErrors());
            return $this->view->getInsert($request, $response, $args);
        }

        $input = $_SESSION[SESSION_REQUEST_INPUT_KEY];
        if (!$res = $this->administratorsModel->insert($input['name'], $input['username'], $input['password'], (int) $input['role_id'])) {
            throw new \Exception("Insert Failure");
        }

        $returned = pg_fetch_all($res);
        $insertedRecordId = $returned[0]['id'];

        $this->systemEvents->insertInfo("Inserted admin", (int) $this->authentication->getUserId(), "id:$insertedRecordId");

        FormHelper::unsetSessionVars();

        $_SESSION[SESSION_ADMIN_NOTICE] = ["Inserted record $insertedRecordId", 'adminNoticeSuccess'];
        return $response->withRedirect($this->router->pathFor(ROUTE_ADMIN_ADMINISTRATORS_RESET)); // reset filter
    }

    public function putUpdate(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality(getRouteName(true, $this->routePrefix, 'update'))) {
            throw new \Exception('No permission.');
        }

        $primaryKey = $args['primaryKey'];

        $this->setRequestInput($request);
        // no boolean fields

        $redirectRoute = getRouteName(true, $this->routePrefix,'index');

        // make sure there is a record for the primary key in the model
        if (!$record = $this->administratorsModel->getPrimaryTableModel()->selectForPrimaryKey($primaryKey)) {
            return SingleTableHelper::updateNoRecord($this->container, $response, $primaryKey, $this->administratorsModel->getPrimaryTableModel(), $this->routePrefix);
        }

        $input = $_SESSION[SESSION_REQUEST_INPUT_KEY];

        // if no changes made, redirect
        // note, if pw and pwconf fields are blank, do not include them in changed fields check
        // debatable whether this should be part of validation and stay on page with error
        $checkChangedFields = [
            'name' => $input['name'],
            'username' => $input['username'],
            'role_id' => $input['role_id'],
        ];
        if (strlen($input['password']) > 0 || strlen($input['password_confirm']) > 0) {
            // password_hash to match db column name
            $checkChangedFields['password_hash'] = password_hash($input['password'], PASSWORD_DEFAULT);
        }
        if (!$this->administratorsSingleTableController->haveAnyFieldsChanged($checkChangedFields, $record)) {
            $_SESSION[SESSION_ADMIN_NOTICE] = ["No changes made (Record $primaryKey)", 'adminNoticeFailure'];
            FormHelper::unsetSessionVars();
            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }

        $this->setValidation($record);

        if (!$this->validator->validate()) {
            // redisplay the form with input values and error(s)
            FormHelper::setFieldErrors($this->validator->getFirstErrors());
            return $this->view->updateView($request, $response, $args);
        }

        if (!$this->administratorsModel->updateByPrimaryKey((int) $primaryKey, $input['name'], $input['username'], (int) $input['role_id'], $input['password'], $record)) {
            throw new \Exception("Update Failure");
        }

        $this->systemEvents->insertInfo("Updated ".$this->administratorsModel::TABLE_NAME, (int) $this->authentication->getUserId(), "id:$primaryKey");

        FormHelper::unsetSessionVars();

        $_SESSION[SESSION_ADMIN_NOTICE] = ["Updated record $primaryKey", 'adminNoticeSuccess'];
        return $response->withRedirect($this->router->pathFor(getRouteName(true, $this->routePrefix,'index')));
    }

    // override for custom validation and return column
    public function getDelete(Request $request, Response $response, $args)
    {
        // make sure the current admin is not deleting themself
        if ((int) ($args['primaryKey']) == $this->container->authentication->user()['id']) {
            throw new \Exception('You cannot delete yourself from administrators');
        }

        // make sure there are no system events for admin being deleted
        if ($this->container->systemEvents->hasForAdmin((int) $args['primaryKey'])) {
            $_SESSION[SESSION_ADMIN_NOTICE] = ["System Events exist for admin id ".$args['primaryKey'], 'adminNoticeFailure'];
            return $response->withRedirect($this->router->pathFor(getRouteName(true, $this->routePrefix,'index')));
        }

        return $this->administratorsSingleTableController->getDeleteHelper($response, $args['primaryKey'],'username', true);
    }
}
