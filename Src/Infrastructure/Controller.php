<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure;

use Slim\Container;

abstract class Controller
{
    protected $container; // dependency injection container
    protected $model;
    protected $view;
    protected $routePrefix;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __get($name)
    {
        return $this->container->{$name};
    }
}
