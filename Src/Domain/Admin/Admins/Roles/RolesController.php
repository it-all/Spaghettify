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
        parent::__construct($container, new RolesModel(), new RolesView($container), 'roles');
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
