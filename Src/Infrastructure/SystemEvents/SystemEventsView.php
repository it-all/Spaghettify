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
    const SESSION_WHERE_COLUMNS = 'systemEventsWhereColumnsInfo';
    const SESSION_WHERE_VALUE_KEY = 'systemEventsWhereField';
    const SESSION_WHERE_FIELD_NAME = 'systemEventsWhere';

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
            if (isset($_SESSION[self::SESSION_WHERE_COLUMNS])) {
                unset($_SESSION[self::SESSION_WHERE_COLUMNS]);
            }
            if (isset($_SESSION[self::SESSION_WHERE_VALUE_KEY])) {
                unset($_SESSION[self::SESSION_WHERE_VALUE_KEY]);
            }
            // redirect to the clean url
            return $response->withRedirect($this->router->pathFor(ROUTE_SYSTEM_EVENTS));
        }

        $whereColumnsInfo = (isset($_SESSION[self::SESSION_WHERE_COLUMNS])) ? $_SESSION[self::SESSION_WHERE_COLUMNS] : null;
        if ($results = pg_fetch_all($this->model->getView($whereColumnsInfo))) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        // determine where field value
        if (isset($_SESSION[SESSION_REQUEST_INPUT_KEY][self::SESSION_WHERE_FIELD_NAME])) {
            $whereFieldValue = $_SESSION[SESSION_REQUEST_INPUT_KEY][self::SESSION_WHERE_FIELD_NAME];
        } elseif (isset($_SESSION[self::SESSION_WHERE_VALUE_KEY])) {
            $whereFieldValue = $_SESSION[self::SESSION_WHERE_VALUE_KEY];
        } else {
            $whereFieldValue = '';
        }

        $whereErrorMessage = FormHelper::getFieldError(self::SESSION_WHERE_FIELD_NAME);
        FormHelper::unsetSessionVars();

        return $this->view->render(
            $response,
            'admin/list.twig',
            [
                'title' => $this->model->getFormalTableName(),
                'primaryKeyColumn' => $this->model->getPrimaryKeyColumnName(),
                'insertLink' => false,
                'whereOpsList' => QueryBuilder::getWhereOperatorsText(),
                'whereValue' => $whereFieldValue,
                'whereErrorMessage' => $whereErrorMessage,
                'whereAction' => ROUTE_SYSTEM_EVENTS,
                'whereFieldName' => self::SESSION_WHERE_FIELD_NAME,
                'isFiltered' => $whereColumnsInfo,
                'resetRoute' => ROUTE_SYSTEM_EVENTS_RESET,
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
