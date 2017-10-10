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

    public function __construct(Container $container, DatabaseTableModel $model, string $routePrefix)
    {
        $this->model = $model;
        $this->routePrefix = $routePrefix;
        parent::__construct($container, $routePrefix.'FilterColumnsInfo', $routePrefix.'FilterValue', $routePrefix.'Filter');
    }

    public function indexView(Response $response, bool $resetFilter = false, string $columns = '*')
    {
        if ($resetFilter) {
            return $this->resetFilter($response, ROUTE_ADMIN_TESTIMONIALS);
        }

        $filterColumnsInfo = (isset($_SESSION[$this->sessionFilterColumnsKey])) ? $_SESSION[$this->sessionFilterColumnsKey] : null;

        if ($results = pg_fetch_all($this->model->select($columns, $this->model->getDefaultOrderByColumnName(), $this->model->getDefaultOrderByAsc(), $filterColumnsInfo))) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        $filterFieldValue = $this->getFilterFieldValue();
        $filterErrorMessage = FormHelper::getFieldError($this->sessionFilterFieldKey);

        // make sure all session input necessary to send to twig is produced above
        FormHelper::unsetSessionVars();

        $insertLink = ($this->authorization->check($this->getAuthorizationMinimumLevel('insert'))) ? ['text' => 'Insert '.$this->model->getFormalTableName(false), 'route' => getRouteName(true, $this->routePrefix, 'insert')] : false;

        return $this->view->render(
            $response,
            'admin/list.twig',
            [
                'title' => $this->model->getFormalTableName(),
                'insertLink' => $insertLink,
                'filterOpsList' => QueryBuilder::getWhereOperatorsText(),
                'filterValue' => $filterFieldValue,
                'filterErrorMessage' => $filterErrorMessage,
                'filterFormAction' => getRouteName(true, $this->routePrefix, 'index'),
                'filterFieldName' => $this->sessionFilterFieldKey,
                'isFiltered' => $filterColumnsInfo,
                'resetFilterRoute' => getRouteName(true, $this->routePrefix, 'index.reset'),
                'updateColumn' => $this->model->getPrimaryKeyColumnName(),
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
