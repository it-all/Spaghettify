<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Roles;

use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\AdminCrudView;
use Slim\Container;

class RolesView extends AdminCrudView
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new RolesModel(), ROUTEPREFIX_ADMIN_ROLES);
    }
}
