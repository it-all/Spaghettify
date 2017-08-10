<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure;

use It_All\Spaghettify\Src\Domain\Admin\NavAdmin;
use Slim\Container;

class AdminView
{
    protected $container; // dependency injection container
    protected $navigationItems;

    public function __construct(Container $container)
    {
        $this->container = $container;

        // Instantiate navigation navbar contents
        $navAdmin = new NavAdmin($container);
        $this->navigationItems = $navAdmin->getSectionsForUser($container->authorization);
    }

    public function __get($name)
    {
        return $this->container->{$name};
    }
}
