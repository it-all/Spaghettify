<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\Log;

namespace It_All\Spaghettify\Src\Infrastructure\SystemEvents;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\ListView;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Container;
use Slim\Http\Response;

class SystemEventsView extends ListView
{
    private $model;

    public function __construct(Container $container)
    {
        parent::__construct($container, 'systemEventsFilterColumnsInfo', 'systemEventsFilterValue', 'systemEventsFilter');
        $this->model = $this->systemEvents; // already in container as a service
    }

    public function indexView(Response $response, bool $resetFilter = false)
    {
        if ($resetFilter) {
            return $this->resetFilter($response, ROUTE_SYSTEM_EVENTS);
        }

        $filterColumnsInfo = (isset($_SESSION[$this->sessionFilterColumnsKey])) ? $_SESSION[$this->sessionFilterColumnsKey] : null;
        if ($results = pg_fetch_all($this->model->getListView($filterColumnsInfo))) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        $filterErrorMessage = FormHelper::getFieldError($this->sessionFilterFieldKey);
        FormHelper::unsetSessionVars();

        return $this->view->render(
            $response,
            'admin/list.twig',
            [
                'title' => $this->model->getListViewTitle(),
                'updateColumn' => $this->model->getUpdateColumnName(),
                'insertLink' => false,
                'filterOpsList' => QueryBuilder::getWhereOperatorsText(),
                'filterValue' => $this->getFilterFieldValue(),
                'filterErrorMessage' => $filterErrorMessage,
                'filterFormAction' => ROUTE_SYSTEM_EVENTS,
                'filterFieldName' => $this->sessionFilterFieldKey,
                'isFiltered' => $filterColumnsInfo,
                'resetFilterRoute' => ROUTE_SYSTEM_EVENTS_RESET,
                'updatePermitted' => false,
                'updateRoute' => false,
                'addDeleteColumn' => false,
                'deleteRoute' => false,
                'results' => $results,
                'numResults' => $numResults,
                'sortColumn' => $this->model->getOrderByColumnName(),
                'sortByAsc' => $this->model->getIsOrderByAsc(),
                'navigationItems' => $this->navigationItems
            ]
        );
    }
}
