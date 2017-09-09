<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\Log;

namespace It_All\Spaghettify\Src\Infrastructure\SystemEvents;
use It_All\Spaghettify\Src\Infrastructure\AdminView;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class SystemEventsView extends AdminView
{
    private $model;

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->model = $this->systemEvents; // already in container as a service
    }

    public function index(Request $request, Response $response, $args)
    {
        if ($results = pg_fetch_all($this->model->getView())) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        return $this->view->render(
            $response,
            'admin/list.twig',
            [
                'title' => $this->model->getFormalTableName(),
                'primaryKeyColumn' => $this->model->getPrimaryKeyColumnName(),
                'insertLink' => false,
                'updatePermitted' => false,
                'updateRoute' => false,
                'addDeleteColumn' => false,
                'deleteRoute' => false,
                'results' => $results,
                'numResults' => $numResults,
                'sortColumn' => $this->model->getDefaultOrderByColumnName(),
                'sortByAsc' => $this->model->getDefaultOrderByAsc(),
                'navigationItems' => $this->navigationItems
            ]
        );
    }
}
