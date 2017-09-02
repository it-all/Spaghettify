<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\CrudController;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminsController extends CrudController
{
    public function __construct(Container $container)
    {
        $this->model = new AdminsModel();
        $this->view = new AdminsView($container);
        $this->routePrefix = 'admins';
        parent::__construct($container);
    }

    private function setValidation()
    {
        $this->validator = $this->validator->withData($_SESSION[SESSION_REQUEST_INPUT_KEY]);

        $rules = [
            'required' => [['name'], ['username'], ['password'], ['password_confirm'], ['role_id']],
            'lengthMin' => [
                ['username', 4],
                ['password', 12]
            ],
            'equals' => [['password', 'password_confirm']]
        ];

        $this->validator->rules($rules);
        $this->validator->rule('regex', 'name', '%^[a-zA-Z\s]+$%')->message('must be letters and spaces only');

        // unique column rule for username
        $this->validator::addRule('unique', function($field, $value, array $params = [], array $fields = []) {
            if (!$params[1]->errors($field)) {
                return !$params[0]->recordExistsForValue($value);
            }
            return true; // skip validation if there is already an error for the field
        }, 'Already exists.');

        $this->validator->rule('unique', 'name', $this->model->getColumnByName('name'), $this->validator);
    }

    public function postInsert(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality($this->routePrefix.'.insert')) {
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

        $values = $_SESSION[SESSION_REQUEST_INPUT_KEY];
        if (!$this->model->insert($values['name'], $values['username'], $values['password'], (int) $values['role_id'])) {
            // redisplay form with errors and input values
            return ($this->view->getInsert($request, $response, $args));
        }

        return $response->withRedirect($this->router->pathFor($this->routePrefix.'.index'));
    }

    public function putUpdate(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality($this->routePrefix.'.update')) {
            throw new \Exception('No permission.');
        }

        $this->setRequestInput($request);
        // no boolean fields

        $redirectRoute = $this->routePrefix.'.index';

        // make sure there is a record for the primary key in the model
        if (!$record = $this->model->selectForPrimaryKey($args['primaryKey'])) {
            $_SESSION['adminNotice'] = [
                "Record ".$args['primaryKey']." Not Found",
                'adminNoticeFailure'
            ];
            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }

        $this->setValidation();


        if (!$this->validator->validate()) {
            // redisplay the form with input values and error(s)
            FormHelper::setFieldErrors($this->validator->getFirstErrors());
            return $this->view->updateView($request, $response, $args);
        }

        if ($this->update($response, $args)) {
            return $response->withRedirect($this->router->pathFor($redirectRoute));
        }
    }

    /**
     * overrride for custom validation
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     * @throws \Exception
     */
    public function getDelete(Request $request, Response $response, $args)
    {
        // make sure the current admin is not deleting themself
        if (intval($args['primaryKey']) == $this->container->authentication->user()['id']) {
            throw new \Exception('You cannot delete yourself from admins');
        }

        return $this->delete($response, $args,'username', true);
    }
}
