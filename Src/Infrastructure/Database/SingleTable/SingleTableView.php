<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\SingleTable;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\ListView;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class SingleTableView extends ListView
{
    protected $routePrefix;
    protected $model;

    public function __construct(
        Container $container,
        DatabaseTableModel $model,
        string $routePrefix
    )
    {
        $this->model = $model;
        $this->routePrefix = $routePrefix;
        parent::__construct($container);
    }

    public function indexView(Response $response, string $columns = '*')
    {
        if ($results = pg_fetch_all($this->model->select($columns, $this->model->getDefaultOrderByColumnName(), $this->model->getDefaultOrderByAsc()))) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        $insertLink = ($this->authorization->check($this->getAuthorizationMinimumLevel('insert'))) ? ['text' => 'Insert '.$this->model->getFormalTableName(false), 'route' => getRouteName(true, $this->routePrefix, 'insert')] : false;

        return $this->view->render(
            $response,
            'admin/list.twig',
            [
                'title' => $this->model->getFormalTableName(),
                'updateColumn' => $this->model->getPrimaryKeyColumnName(),
                'insertLink' => $insertLink,
                'filterOpsList' => QueryBuilder::getWhereOperatorsText(),
//                'filterValue' => $filterFieldValue,
//                'filterErrorMessage' => $filterErrorMessage,
//                'filterFormAction' => ROUTE_ADMIN_ADMINS,
//                'filterFieldName' => self::SESSION_FILTER_FIELD_NAME,
//                'isFiltered' => $whereColumnsInfo,
//                'resetFilterRoute' => ROUTE_ADMIN_ADMINS_RESET,
                'updatePermitted' => $this->authorization
                    ->check($this->getAuthorizationMinimumLevel('update')),
                'updateRoute' => getRouteName(true, $this->routePrefix, 'update', 'put'),
                'addDeleteColumn' => $this->authorization
                    ->check($this->getAuthorizationMinimumLevel('delete')),
                'deleteRoute' => getRouteName(true, $this->routePrefix, 'delete'),
                'results' => $results,
                'numResults' => $numResults,
                'sortColumn' => $this->model->getDefaultOrderByColumnName(),
                'sortByAsc' => $this->model->getDefaultOrderByAsc(),
                'navigationItems' => $this->navigationItems
            ]
        );
    }

    public function getInsert(Request $request, Response $response, $args)
    {
        return $this->insertView($request, $response, $args);
    }

    /** this can be called for both the initial get and the posted form if errors exist (from controller) */
    public function insertView(Request $request, Response $response, $args)
    {
        $formFieldData = ($request->isGet()) ? null : $_SESSION[SESSION_REQUEST_INPUT_KEY];

        $form = new DatabaseTableForm($this->model, $this->router->pathFor(getRouteName(true, $this->routePrefix, 'insert', 'post')), $this->csrf->getTokenNameKey(), $this->csrf->getTokenName(), $this->csrf->getTokenValueKey(), $this->csrf->getTokenValue(), 'insert', $formFieldData);
        FormHelper::unsetSessionVars();

        return $this->view->render(
            $response,
            'admin/form.twig',
            [
                'title' => 'Insert '. $this->model->getFormalTableName(false),
                'form' => $form,
                'navigationItems' => $this->navigationItems
            ]
        );
    }

    public function getUpdate(Request $request, Response $response, $args)
    {
        return $this->updateView($request, $response, $args);
    }

    /** this can be called for both the initial get and the posted form if errors exist (from controller) */
    public function updateView(Request $request, Response $response, $args)
    {
        // make sure there is a record for the model
        if (!$record = $this->model->selectForPrimaryKey($args['primaryKey'])) {
            return SingleTableHelper::updateNoRecord($this->container, $response, $args['primaryKey'], $this->model, $this->routePrefix);
        }

        $formFieldData = ($request->isGet()) ? $record : $_SESSION[SESSION_REQUEST_INPUT_KEY];

        $form = new DatabaseTableForm($this->model, $this->router->pathFor(getRouteName(true, $this->routePrefix, 'update', 'put'), ['primaryKey' => $args['primaryKey']]), $this->csrf->getTokenNameKey(), $this->csrf->getTokenName(), $this->csrf->getTokenValueKey(), $this->csrf->getTokenValue(), 'update', $formFieldData);
        FormHelper::unsetSessionVars();

        return $this->view->render(
            $response,
            'admin/form.twig',
            [
                'title' => 'Update ' . $this->model->getFormalTableName(false),
                'form' => $form,
                'primaryKey' => $args['primaryKey'],
                'navigationItems' => $this->navigationItems
            ]
        );
    }
}
