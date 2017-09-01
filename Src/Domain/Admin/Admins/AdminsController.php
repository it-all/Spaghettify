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

    public function postInsert(Request $request, Response $response, $args)
    {

        if (!$this->authorization->checkFunctionality($this->routePrefix.'.insert')) {
            throw new \Exception('No permission.');
        }

        $this->setRequestInput($request);
        // no boolean fields to add

        $this->validator = $this->validator->withData($_SESSION[SESSION_REQUEST_INPUT_KEY]);

        $rules = [
            'required' => [['name'], ['username'], ['password'], ['password_confirm'], ['role_id']],
            'alpha' => 'name',
            'lengthMin' => ['username', 4]
        ];
        $this->validator->rules($rules);

        // unique column rule for username
        $this->validator::addRule('unique', function($field, $value, array $params = [], array $fields = []) {
            if (!$params[1]->errors($field)) {
                return !$params[0]->recordExistsForValue($value);
            }
            return true; // skip validation if there is already an error for the field
        }, 'Already exists.');

        $this->validator->rule('unique', 'name', $this->model->getColumnByName('name'), $this->validator);



        if (!$this->validator->validate()) {
            // redisplay the form with input values and error(s)
            FormHelper::setFieldErrors($this->validator->getFirstErrors());
            return $this->view->getInsert($request, $response, $args);
        }

        die ('valid');
        if ($this->insert()) {
            return $response->withRedirect($this->router->pathFor($this->routePrefix.'.index'));
        }

        /* old
        $this->setRequestInput($request);

        // custom validation
        if ($this->model->checkRecordExistsForUsername($_SESSION[SESSION_REQUEST_INPUT_KEY]['username'])) {
            $_SESSION['generalFormError'] = 'Username already exists';
            $error = true;
        } elseif (!$this->insert()) {
            $error = true;
        } else { // successful insert
            return $response->withRedirect($this->router->pathFor($this->routePrefix.'.index'));
        }

        if ($error) {
            // redisplay form with errors and input values
            return ($this->view->getInsert($request, $response, $args));
        }
        */
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
