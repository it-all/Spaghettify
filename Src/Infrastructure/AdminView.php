<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure;

use It_All\Spaghettify\Src\Domain\Admin\NavAdmin;
use Slim\Container;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;

class AdminView extends View
{
    protected $navigationItems;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        // Instantiate navigation navbar contents
        $navAdmin = new NavAdmin($container);
        $this->navigationItems = $navAdmin->getNavForUser($container->authorization);
    }

    protected function getAuthorizationMinimumLevel(string $type = 'index')
    {
        if (!isset($this->routePrefix)) {
            throw new \Exception("The routePrefix property must be set.");
        }

        if ($type != 'index' && $type != 'insert' && $type != 'update' && $type != 'delete') {
            throw new \Exception("Invalid type $type");
        }

        // returns specific category.function if it exists or category if it exists
        if (isset($this->container->settings['authorization'][getRouteName(true, $this->routePrefix, $type)])) {
            return $this->container->settings['authorization'][getRouteName(true, $this->routePrefix, $type)];
        } elseif (isset($this->container->settings['authorization'][$this->routePrefix])) {
            return $this->container->settings['authorization'][$this->routePrefix];
        } else {
            throw new \Exception('No authorization level set for '.$type.' to '.$this->routePrefix);
        }
    }
}
