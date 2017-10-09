<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Roles;

use It_All\Spaghettify\Src\Infrastructure\Database\SingleTable\SingleTableController;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class RolesController extends SingleTableController
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new RolesModel(), new RolesView($container), ROUTEPREFIX_ADMIN_ROLES);
    }

    // overrride for custom return column
    public function getDelete(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality(getRouteName(true, $this->routePrefix, 'delete'))) {
            throw new \Exception('No permission.');
        }

        return $this->getDeleteHelper($response, $args['primaryKey'],'role', true);
    }
}
