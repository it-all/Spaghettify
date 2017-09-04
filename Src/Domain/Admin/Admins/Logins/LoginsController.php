<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Roles;

use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\CrudController;
use Slim\Container;

class LoginsController extends CrudController
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new LoginsModel(), new LoginsView($container), 'logins');
    }
}
