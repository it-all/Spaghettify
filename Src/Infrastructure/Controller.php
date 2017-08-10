<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure;

use Slim\Container;
use Slim\Http\Request;

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

    /** may want a config var bool trimAllInputs */
    protected function setFormInput(Request $request)
    {
        $_SESSION['formInput'] = [];
        foreach ($request->getParsedBody() as $key => $value) {
            $_SESSION['formInput'][$key] = trim($value);
        }
    }
}
