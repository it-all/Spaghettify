<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\CrudController;
use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\CrudHelper;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminsController extends CrudController
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new AdminsModel(), new AdminsView($container), ROUTEPREFIX_ADMIN_ADMINS);
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

            $this->validator->rule('unique', 'username', $this->model->getColumnByName('name'), $this->validator);
        }
    }

    // parse the where field
    public function getWhereFilterColumns(string $whereFieldValue): ?array
    {
        $whereColumnsInfo = [];
        $whereParts = explode(",", $whereFieldValue);
        if (strlen($whereParts[0]) == 0) {
            FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => 'Not Entered']);
            return null;
        } else {

            foreach ($whereParts as $whereFieldOperatorValue) {
                //field:operator:value
                $whereFieldOperatorValueParts = explode(":", $whereFieldOperatorValue);
                if (count($whereFieldOperatorValueParts) != 3) {
                    FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => 'Malformed']);
                    return null;
                }
                $columnName = trim($whereFieldOperatorValueParts[0]);
                $whereOperator = strtoupper(trim($whereFieldOperatorValueParts[1]));
                $whereValue = trim($whereFieldOperatorValueParts[2]);

                // validate the column name
                try {
                    $columnNameSql = $this->model::getColumnNameSqlForColumnName($columnName);
                } catch (\Exception $e) {
                    FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => "$columnName not found"]);
                    return null;
                }

                // validate the operator
                if (!QueryBuilder::validateWhereOperator($whereOperator)) {
                    FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => "Invalid Operator $whereOperator"]);
                    return null;
                }

                // null value only valid with IS and IS NOT operators
                if (strtolower($whereValue) == 'null') {
                    if ($whereOperator != 'IS' && $whereOperator != 'IS NOT') {
                        FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => "Mismatched null, $whereOperator"]);
                        return null;
                    }
                    $whereValue = null;
                }

                if (!isset($whereColumnsInfo[$columnNameSql])) {
                    $whereColumnsInfo[$columnNameSql] = [
                        'operators' => [$whereOperator],
                        'values' => [$whereValue]
                    ];
                } else {
                    $whereColumnsInfo[$columnNameSql]['operators'][] = $whereOperator;
                    $whereColumnsInfo[$columnNameSql]['values'][] = $whereValue;
                }
            }
        }

        return $whereColumnsInfo;
    }

    public function postIndexFilter(Request $request, Response $response, $args)
    {
        $this->setRequestInput($request);

        if (!isset($_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_WHERE_FIELD_NAME])) {
            throw new \Exception("where session input must be set");
        }

        if (!$whereColumnsInfo = $this->getWhereFilterColumns($_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_WHERE_FIELD_NAME])) {
            // redisplay form with error
            return $this->view->indexViewAdmins($response);
        } else {
            $_SESSION[$this->view::SESSION_WHERE_COLUMNS] = $whereColumnsInfo;
            $_SESSION[$this->view::SESSION_WHERE_VALUE_KEY] = $_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_WHERE_FIELD_NAME];
            FormHelper::unsetSessionVars();
            return $response->withRedirect($this->router->pathFor(ROUTE_ADMIN_ADMINS));
        }
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
        if (!$res = $this->model->insert($input['name'], $input['username'], $input['password'], (int) $input['role_id'])) {
            throw new \Exception("Insert Failure");
        }

        $returned = pg_fetch_all($res);
        $insertedRecordId = $returned[0]['id'];

        $tableName = $this->model->getTableName();
        $this->systemEvents->insertInfo("Inserted $tableName", (int) $this->authentication->getUserId(), "id:$insertedRecordId");

        FormHelper::unsetSessionVars();

        $_SESSION[SESSION_ADMIN_NOTICE] = ["Inserted record $insertedRecordId", 'adminNoticeSuccess'];
        return $response->withRedirect($this->router->pathFor(ROUTE_ADMIN_ADMINS_RESET)); // reset filter
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

        $tableName = $this->model->getTableName();

        // make sure there is a record for the primary key in the model
        if (!$record = $this->model->selectForPrimaryKey($primaryKey)) {
            return CrudHelper::updateNoRecord($this->container, $response, $primaryKey, $this->model, $this->routePrefix);
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
        if (!$this->haveAnyFieldsChanged($checkChangedFields, $record)) {
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

        if (!$this->model->updateByPrimaryKey((int) $primaryKey, $input['name'], $input['username'], (int) $input['role_id'], $input['password'], $record)) {
            throw new \Exception("Update Failure");
        }

        $this->systemEvents->insertInfo("Updated $tableName", (int) $this->authentication->getUserId(), "id:$primaryKey");

        FormHelper::unsetSessionVars();

        $_SESSION[SESSION_ADMIN_NOTICE] = ["Updated record $primaryKey", 'adminNoticeSuccess'];
        return $response->withRedirect($this->router->pathFor(getRouteName(true, $this->routePrefix,'index')));
    }

    // override for custom validation and return column
    public function getDelete(Request $request, Response $response, $args)
    {
        // make sure the current admin is not deleting themself
        if (intval($args['primaryKey']) == $this->container->authentication->user()['id']) {
            throw new \Exception('You cannot delete yourself from admins');
        }

        return $this->getDeleteHelper($response, $args['primaryKey'],'username', true);
    }
}
