<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\Log;

namespace It_All\Spaghettify\Src\Infrastructure\SystemEvents;
use It_All\Spaghettify\Src\Infrastructure\AdminView;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class SystemEventsView extends AdminView
{
    private $model;
    const SESSION_FILTER_COLUMNS = 'systemEventsFilterColumnsInfo';
    const SESSION_FILTER_VALUE_KEY = 'systemEventsFilterField';
    const SESSION_FILTER_FIELD_NAME = 'systemEventsFilter';

    public function __construct(Container $container)
    {
        parent::__construct($container);
        $this->model = $this->systemEvents; // already in container as a service
    }

    public function index(Request $request, Response $response, $args)
    {
        $this->indexView($response);
    }

    public function indexResetFilter(Request $request, Response $response, $args)
    {
        // redirect to the clean url
        return $this->indexView($response, true);
    }

    public function indexView(Response $response, bool $resetFilter = false)
    {
        if ($resetFilter) {
            if (isset($_SESSION[self::SESSION_FILTER_COLUMNS])) {
                unset($_SESSION[self::SESSION_FILTER_COLUMNS]);
            }
            if (isset($_SESSION[self::SESSION_FILTER_VALUE_KEY])) {
                unset($_SESSION[self::SESSION_FILTER_VALUE_KEY]);
            }
            // redirect to the clean url
            return $response->withRedirect($this->router->pathFor(ROUTE_SYSTEM_EVENTS));
        }

        $whereColumnsInfo = (isset($_SESSION[self::SESSION_FILTER_COLUMNS])) ? $_SESSION[self::SESSION_FILTER_COLUMNS] : null;
        if ($results = pg_fetch_all($this->model->getView($whereColumnsInfo))) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        // determine where field value
        if (isset($_SESSION[SESSION_REQUEST_INPUT_KEY][self::SESSION_FILTER_FIELD_NAME])) {
            $filterFieldValue = $_SESSION[SESSION_REQUEST_INPUT_KEY][self::SESSION_FILTER_FIELD_NAME];
        } elseif (isset($_SESSION[self::SESSION_FILTER_VALUE_KEY])) {
            $filterFieldValue = $_SESSION[self::SESSION_FILTER_VALUE_KEY];
        } else {
            $filterFieldValue = '';
        }

        $filterErrorMessage = FormHelper::getFieldError(self::SESSION_FILTER_FIELD_NAME);
        FormHelper::unsetSessionVars();

        return $this->view->render(
            $response,
            'admin/list.twig',
            [
                'title' => $this->model->getFormalTableName(),
                'primaryKeyColumn' => $this->model->getPrimaryKeyColumnName(),
                'insertLink' => false,
                'filterOpsList' => QueryBuilder::getWhereOperatorsText(),
                'filterValue' => $filterFieldValue,
                'filterErrorMessage' => $filterErrorMessage,
                'filterFormAction' => ROUTE_SYSTEM_EVENTS,
                'filterFieldName' => self::SESSION_FILTER_FIELD_NAME,
                'isFiltered' => $whereColumnsInfo,
                'resetFilterRoute' => ROUTE_SYSTEM_EVENTS_RESET,
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
