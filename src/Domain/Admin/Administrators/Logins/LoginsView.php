<?php
declare(strict_types=1);

namespace Domain\Admin\Administrators\Logins;

use Infrastructure\ListView;
use Slim\Container;

class LoginsView extends ListView
{
    public function __construct(Container $container)
    {
        parent::__construct($container, 'logins', ROUTE_LOGIN_ATTEMPTS, new LoginsModel(), ROUTE_LOGIN_ATTEMPTS_RESET);
    }
}
