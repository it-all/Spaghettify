<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\CRUD;

use It_All\Spaghettify\Src\Infrastructure\Controller;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class CrudController extends Controller
{
    protected $model;
    protected $view;
    protected $routePrefix;

    public function __construct(Container $container, $model, $view, $routePrefix)
    {
        $this->model = $model;
        $this->view = $view;
        $this->routePrefix = $routePrefix;
        parent::__construct($container);
    }

    public function postInsert(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality(getRouteName(true, $this->routePrefix, 'insert'))) {
            throw new \Exception('No permission.');
        }

        $this->setRequestInput($request);
        $this->addBooleanFieldsToInput();

        $this->validator = $this->validator->withData($_SESSION[SESSION_REQUEST_INPUT_KEY], FormHelper::getDatabaseTableValidationFields($this->model));

        $this->validator->mapFieldsRules(FormHelper::getDatabaseTableValidation($this->model));

        if (count($this->model->getUniqueColumns()) > 0) {
            $this->validator::addRule('unique', function($field, $value, array $params = [], array $fields = []) {
                if (!$params[1]->errors($field)) {
                    return !$params[0]->recordExistsForValue($value);
                }
                return true; // skip validation if there is already an error for the field
            }, 'Already exists.');

            foreach ($this->model->getUniqueColumns() as $databaseColumnModel) {
                $this->validator->rule('unique', $databaseColumnModel->getName(), $databaseColumnModel, $this->validator);
            }
        }

        if (!$this->validator->validate()) {
            // redisplay the form with input values and error(s)
            FormHelper::setFieldErrors($this->validator->getFirstErrors());
            return $this->view->insertView($request, $response, $args);
        }

        try {
            $this->insert();
        } catch (\Exception $e) {
            throw new \Exception("Insert failure. ".$e->getMessage());
        }

        FormHelper::unsetSessionVars();
        return $response->withRedirect($this->router->pathFor(getRouteName(true, $this->routePrefix, 'index')));
    }

    private function addBooleanFieldsToInput()
    {
        foreach ($this->model->getColumns() as $databaseColumnModel) {
            if ($databaseColumnModel->isBoolean() && !isset($_SESSION[SESSION_REQUEST_INPUT_KEY][$databaseColumnModel->getName()])) {
                $_SESSION[SESSION_REQUEST_INPUT_KEY][$databaseColumnModel->getName()] = 'f';
            }
        }
    }

    public function putUpdate(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality(getRouteName(true, $this->routePrefix, 'update'))) {
            throw new \Exception('No permission.');
        }

        $this->setRequestInput($request);
        $this->addBooleanFieldsToInput();

        $redirectRoute = getRouteName(true, $this->routePrefix, 'index');

        // make sure there is a record for the primary key in the model
        if (!$record = $this->model->selectForPrimaryKey($args['primaryKey'])) {
            $eventNote = $this->model->getPrimaryKeyColumnName().": ".$args['primaryKey'].", Table: ".$this->model->getTableName();
            $this->systemEvents->insertWarning('Record not found for update', (int) $this->authentication->getUserId(), $eventNote);
            $_SESSION[SESSION_ADMIN_NOTICE] = ["Record ".$args['primaryKey']." Not Found", 'adminNoticeFailure'];
            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }

        // if no changes made, redirect
        // debatable whether this should be part of validation and stay on page with error
        if (!$this->haveAnyFieldsChanged($_SESSION[SESSION_REQUEST_INPUT_KEY], $record)) {
            $_SESSION[SESSION_ADMIN_NOTICE] = ["No changes made (Record ".$args['primaryKey'].")", 'adminNoticeFailure'];
            FormHelper::unsetSessionVars();
            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }

        $this->validator = $this->validator->withData($_SESSION[SESSION_REQUEST_INPUT_KEY], FormHelper::getDatabaseTableValidationFields($this->model));

        $this->validator->mapFieldsRules(FormHelper::getDatabaseTableValidation($this->model));

        if (count($this->model->getUniqueColumns()) > 0) {
            $this->validator::addRule('unique', function($field, $value, array $params = [], array $fields = []) {
                if (!$params[1]->errors($field)) {
                    return !$params[0]->recordExistsForValue($value);
                }
                return true; // skip validation if there is already an error for the field
            }, 'Already exists.');

            foreach ($this->model->getUniqueColumns() as $databaseColumnModel) {
                // only set rule for changed columns
                if ($_SESSION[SESSION_REQUEST_INPUT_KEY][$databaseColumnModel->getName()] != $record[$databaseColumnModel->getName()]) {
                    $this->validator->rule('unique', $databaseColumnModel->getName(), $databaseColumnModel, $this->validator);
                }
            }
        }

        if (!$this->validator->validate()) {
            // redisplay the form with input values and error(s)
            FormHelper::setFieldErrors($this->validator->getFirstErrors());
            return $this->view->updateView($request, $response, $args);
        }

        try {
            $this->update($response, $args);
        } catch (\Exception $e) {
            throw new \Exception("Update failure. ".$e->getMessage());
        }

        FormHelper::unsetSessionVars();
        return $response->withRedirect($this->router->pathFor($redirectRoute));
    }

    public function getDelete(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality(getRouteName(true, $this->routePrefix, 'delete'))) {
            throw new \Exception('No permission.');
        }

        try {
            $this->delete($response, $args);
        } catch (\Exception $e) {
            // no need to do anything, just redirect with error message already set
        }

        $redirectRoute = getRouteName(true, $this->routePrefix, 'index');
        return $response->withRedirect($this->router->pathFor($redirectRoute));
    }

    /**
     * @param array $newValues
     * @param array $record
     * @return bool
     */
    protected function haveAnyFieldsChanged(array $newValues, array $record): bool
    {
        foreach ($newValues as $columnName => $value) {
            // throw out any new values that are not model table columns
            if ($column = $this->model->getColumnByName($columnName) && $value != $record[$columnName]) {
                return true;
            }
        }

        return false;
    }

    protected function insert(bool $sendEmail = false)
    {
        // attempt insert
        try {
            $res = $this->model->insertRecord($_SESSION[SESSION_REQUEST_INPUT_KEY]);
            $returned = pg_fetch_all($res);
            $primaryKeyColumnName = $this->model->getPrimaryKeyColumnName();
            $insertedRecordId = $returned[0][$primaryKeyColumnName];
            $tableName = $this->model->getTableName();

            $eventNote = "$primaryKeyColumnName: $insertedRecordId, Table: $tableName";
            $this->systemEvents->insertInfo('Inserted database record', (int) $this->authentication->getUserId(), $eventNote);

            if ($sendEmail) {
                $settings = $this->container->get('settings');
                $this->mailer->send(
                    $_SERVER['SERVER_NAME'] . " Event",
                    "Inserted into $tableName.\n See event log for details.",
                    [$settings['emails']['programmer']]
                );
            }

            $_SESSION[SESSION_ADMIN_NOTICE] = ["Inserted record $insertedRecordId", 'adminNoticeSuccess'];

            return true;

        } catch(\Exception $exception) {
            throw $exception;
        }
    }

    protected function update(Response $response, $args, bool $sendEmail = false)
    {
        // attempt to update the model
        try {
            $this->model->updateRecordByPrimaryKey($_SESSION[SESSION_REQUEST_INPUT_KEY], $args['primaryKey']);

            $primaryKeyColumnName = $this->model->getPrimaryKeyColumnName();
            $updatedRecordId = $args['primaryKey'];
            $tableName = $this->model->getTableName();

            $eventNote = "$primaryKeyColumnName: $updatedRecordId, Table: $tableName";
            $this->systemEvents->insertInfo('Updated database record', (int) $this->authentication->getUserId(), $eventNote);

            if ($sendEmail) {
                $settings = $this->container->get('settings');
                $this->mailer->send(
                    $_SERVER['SERVER_NAME'] . " Event",
                    "Updated $tableName.\n See event log for details.",
                    [$settings['emails']['programmer']]
                );
            }

            $_SESSION[SESSION_ADMIN_NOTICE] = ["Updated record $updatedRecordId", 'adminNoticeSuccess'];

            return true;

        } catch(\Exception $exception) {
            throw $exception;
        }
    }

    protected function delete(Response $response, $args, string $returnColumn = null, bool $sendEmail = false)
    {
        $primaryKey = $args['primaryKey'];
        $primaryKeyColumnName = $this->model->getPrimaryKeyColumnName();
        $tableName = $this->model->getTableName();
        $eventNote = "$primaryKeyColumnName: $primaryKey, Table: $tableName";

        if (!$res = $this->model->deleteByPrimaryKey($primaryKey, $returnColumn)) {
            $this->systemEvents->insertWarning('Primary key not found for delete', (int) $this->authentication->getUserId(), $eventNote);
            $_SESSION[SESSION_ADMIN_NOTICE] = [$primaryKey.' not found', 'adminNoticeFailure'];
            return false;
        }

        $adminMessage = 'Deleted record '.$primaryKey;
        if ($returnColumn != null) {
            $returned = pg_fetch_all($res);
            $eventNote .= ", $returnColumn: ".$returned[0][$returnColumn];
            $adminMessage .= " ($returnColumn ".$returned[0][$returnColumn].")";
        }

        $this->systemEvents->insertInfo('Deleted database record', (int) $this->authentication->getUserId(), $eventNote);

        if ($sendEmail) {
            $settings = $this->container->get('settings');
            $this->mailer->send(
                $_SERVER['SERVER_NAME'] . " Event",
                "Deleted record from $tableName.\nSee event log for details.",
                [$settings['emails']['programmer']]
            );
        }

        $_SESSION[SESSION_ADMIN_NOTICE] = [$adminMessage, 'adminNoticeSuccess'];
        return true;
    }
}
