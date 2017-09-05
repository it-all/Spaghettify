<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Logins;

use It_All\Spaghettify\Src\Infrastructure\AdminView;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class LoginsView extends AdminView
{
    private $model;

    public function __construct(Container $container)
    {
        $this->model = new LoginsModel();
        parent::__construct($container);
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
