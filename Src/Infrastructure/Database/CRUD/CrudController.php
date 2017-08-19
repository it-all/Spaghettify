<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\CRUD;

use It_All\Spaghettify\Src\Infrastructure\Controller;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use It_All\Spaghettify\Src\Infrastructure\Utilities\SimpleValidatorExtension;
use Slim\Http\Request;
use Slim\Http\Response;

class CrudController extends Controller
{
    protected $model;
    protected $routePrefix;

    public function postInsert(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality($this->routePrefix.'.insert')) {
            throw new \Exception('No permission.');
        }

        $this->setRequestInput($request);

        // todo validate unique columns
        $validationResult = SimpleValidatorExtension::validate($_SESSION[SESSION_REQUEST_INPUT_KEY], FormHelper::getDatabaseTableValidation($this->model));
        if (!$validationResult->isSuccess()) {

//
//            if (!$this->validator->validate($_SESSION[SESSION_REQUEST_INPUT_KEY], FormHelper::getDatabaseTableValidation($this->model))) {
            // redisplay form with errors and input values
//            FormHelper::setFieldErrors($this->validator->getErrors());
            FormHelper::setFieldErrors($validationResult->getErrors());
            return ($this->view->getInsert($request, $response, $args));
        }

        if ($this->insert()) {
            return $response->withRedirect($this->router->pathFor($this->routePrefix.'.index'));
        }
    }

    public function putUpdate(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality($this->routePrefix.'.update')) {
            throw new \Exception('No permission.');
        }

        $this->setRequestInput($request);

        $redirectRoute = $this->routePrefix.'.index';

        // todo put the following 2 validations into validator using model fn calls?

        // make sure there is a record for the primary key in the model
        if (!$record = $this->model->selectForPrimaryKey($args['primaryKey'])) {
            $_SESSION['adminNotice'] = [
                "Record ".$args['primaryKey']." Not Found",
                'adminNoticeFailure'
            ];
            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }

        // if no changes made, redirect
        if (!$this->haveAnyFieldsChanged($_SESSION[SESSION_REQUEST_INPUT_KEY], $record)) {
            $_SESSION['adminNotice'] = ["No changes made", 'adminNoticeFailure'];
            unset($_SESSION[SESSION_REQUEST_INPUT_KEY]);
            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }

        // todo validate unique columns

        if (!$this->validator->validate($_SESSION[SESSION_REQUEST_INPUT_KEY], FormHelper::getDatabaseTableValidation($this->model))) {
            // redisplay form with errors and input values
            FormHelper::setFieldErrors($this->validator->getErrors());
            return ($this->view->getUpdate($request, $response, $args));
        }

        if ($this->update($response, $args)) {
            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }
    }

    public function getDelete(Request $request, Response $response, $args)
    {
        return $this->delete($response, $args);
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
            $res = $this->model->insert($_SESSION[SESSION_REQUEST_INPUT_KEY]);
            FormHelper::unsetSessionVars();
            $returned = pg_fetch_all($res);
            $message = 'Inserted record '.$returned[0][$this->model->getPrimaryKeyColumnName()].
                ' into '.$this->model->getTableName();
            $this->logger->addInfo($message);
            if ($sendEmail) {
                $settings = $this->container->get('settings');
                $this->mailer->send(
                    $_SERVER['SERVER_NAME'] . " Event",
                    "Inserted into ".$this->model->getTableName()."\n See event log for details.",
                    [$settings['emails']['owner']]
                );
            }
            $_SESSION['adminNotice'] = [$message, 'adminNoticeSuccess'];

            return true;

        } catch(\Exception $exception) {
            throw $exception;
        }
    }

    protected function update(Response $response, $args)
    {
        // attempt to update the model
        try {
            $this->model->updateByPrimaryKey($_SESSION[SESSION_REQUEST_INPUT_KEY], $args['primaryKey']);
            FormHelper::unsetSessionVars();
            $message = 'Updated record '.$args['primaryKey'];
            $this->logger->addInfo($message . ' in '. $this->model->getTableName());
            $_SESSION['adminNotice'] = [$message, 'adminNoticeSuccess'];

            return true;

        } catch(\Exception $exception) {
            throw $exception;
        }
    }

    protected function delete(Response $response, $args, string $returnColumn = null, bool $sendEmail = false)
    {
        if (!$this->authorization->checkFunctionality($this->routePrefix.'.delete')) {
            throw new \Exception('No permission.');
        }

        $primaryKey = $args['primaryKey'];
        $redirectRoute = $this->routePrefix.'.index';

        if ($res = $this->model->deleteByPrimaryKey($primaryKey, $returnColumn)) {
            $message = 'Deleted record '.$primaryKey;
            if ($returnColumn != null) {
                $returned = pg_fetch_all($res);
                $message .= ' ('.$returnColumn.' '.$returned[0][$returnColumn].')';
            }
            $this->logger->addInfo($message . " from ".$this->model->getTableName()."");
            if ($sendEmail) {
                $settings = $this->container->get('settings');
                $this->mailer->send(
                    $_SERVER['SERVER_NAME'] . " Event",
                    "Deleted record from ".$this->model->getTableName().".\nSee event log for details.",
                    [$settings['emails']['owner']]
                );
            }
            $_SESSION['adminNotice'] = [$message, 'adminNoticeSuccess'];

            return $response->withRedirect($this->router->pathFor($redirectRoute));

        } else {

            $this->logger->addWarning("primary key $primaryKey for ".$this->model->getTableName()." not found for deletion. IP: " . $_SERVER['REMOTE_ADDR']);

            $settings = $this->container->get('settings');
            $this->mailer->send($_SERVER['SERVER_NAME'] . " Event", "primary key $primaryKey not found for deletion. Check event log for details.", [$settings['emails']['programmer']]);

            $_SESSION['adminNotice'] = [$primaryKey.' not found', 'adminNoticeFailure'];

            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }
    }
}
