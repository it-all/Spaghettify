<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Roles;

use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\CrudController;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class RolesController extends CrudController
{
    public function __construct(Container $container)
    {
        $this->model = new RolesModel();
        $this->view = new RolesView($container);
        $this->routePrefix = 'roles';
        parent::__construct($container);
    }

    /**
     * override for custom return column
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getDelete(Request $request, Response $response, $args)
    {
        return $this->delete($response, $args,'role');
    }
}
