<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Logins;

use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\AdminCrudView;
use Slim\Container;

class LoginsView extends AdminCrudView
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new LoginsModel(), 'logins');
    }
}
