<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\Spaghettify\Src\Infrastructure\CrudController;
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

    /**
     * override for custom validation
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function postInsert(Request $request, Response $response, $args)
    {
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
