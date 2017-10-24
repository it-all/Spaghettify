<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\Log;

namespace It_All\Spaghettify\Src\Infrastructure\SystemEvents;
use It_All\Spaghettify\Src\Infrastructure\ListView;
use Slim\Container;

class SystemEventsView extends ListView
{
    public function __construct(Container $container)
    {
        // model already in container as a service
        parent::__construct($container, 'systemEvents', ROUTE_SYSTEM_EVENTS, $container->systemEvents, ROUTE_SYSTEM_EVENTS_RESET);
    }
}
