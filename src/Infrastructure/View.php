<?php
declare(strict_types=1);

namespace Infrastructure;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class View
{
    protected $container; // dependency injection container

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __get($name)
    {
        return $this->container->{$name};
    }

    public function pageNotFound(Request $request, Response $response)
    {
        return $this->view->render(
            $response,
            'frontend/pageNotFound.twig',
            ['title' => 'Spaghettify', 'pageType' => 'public', 'adminLinkRoute' => $this->authentication->getAdminHomeRouteForUser()]
        );
    }
}
