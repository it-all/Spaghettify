<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure;

use It_All\Spaghettify\Src\Infrastructure\Database\SingleTable\SingleTableModel;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\Database\TableModel;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class ListView extends AdminView
{
    protected $sessionFilterColumnsKey;
    protected $sessionFilterValueKey;
    protected $sessionFilterFieldKey;
    private $indexRoute;
    protected $model;
    private $template;
    private $filterResetRoute;
    private $insertLink; // false or ['text' => {link text}, 'route' => {route}]
    private $updatePermitted;
    private $updateColumn;
    private $updateRoute;
    private $addDeleteColumn;
    private $deleteRoute;

    public function __construct(Container $container, string $sessionFilterColumnsKey, string $sessionFilterValueKey, string $sessionFilterFieldKey, string $indexRoute, TableModel $model, string $filterResetRoute, string $template = 'admin/list.twig')
    {
        $this->sessionFilterColumnsKey = $sessionFilterColumnsKey;
        $this->sessionFilterValueKey = $sessionFilterValueKey;
        $this->sessionFilterFieldKey = $sessionFilterFieldKey;
        $this->indexRoute = $indexRoute;
        $this->model = $model;
        $this->template = $template;
        $this->filterResetRoute = $filterResetRoute;
        $this->updatePermitted = false; // initialize
        $this->updateColumn = null; // initialize
        $this->updateRoute = null; // initialize
        $this->addDeleteColumn = false; // initialize
        $this->deleteRoute = null; // initialize
        $this->insertLink = false; // initialize
        parent::__construct($container);
    }

    protected function setInsert($insertLink)
    {
        $this->insertLink = $insertLink;
    }

    protected function setUpdate(bool $updatePermitted, ?string $updateColumn, ?string $updateRoute)
    {
        if ($updatePermitted && ($updateColumn == null || $updateRoute ==null)) {
            throw new \Exception("update column and route must be defined");
        }
        $this->updatePermitted = $updatePermitted; // initialize
        $this->updateColumn = $updateColumn; // initialize
        $this->updateRoute = $updateRoute; // initialize
    }

    protected function setDelete(bool $addDeleteColumn, ?string $deleteRoute)
    {
        if ($addDeleteColumn && $deleteRoute == null) {
            throw new \Exception("delete route must be defined");
        }
        $this->addDeleteColumn = $addDeleteColumn;
        $this->deleteRoute = $deleteRoute;
    }

    public function index(Request $request, Response $response, $args)
    {
        return $this->indexView($response);
    }

    public function indexResetFilter(Request $request, Response $response, $args)
    {
        // redirect to the clean url
        return $this->indexView($response, true);
    }

    private function indexView(Response $response, bool $resetFilter = false)
    {
        if ($resetFilter) {
            return $this->resetFilter($response, $this->indexRoute);
        }

        $filterColumnsInfo = (isset($_SESSION[$this->sessionFilterColumnsKey])) ? $_SESSION[$this->sessionFilterColumnsKey] : null;
        if ($results = pg_fetch_all($this->model->select($filterColumnsInfo))) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        $filterFieldValue = $this->getFilterFieldValue();
        $filterErrorMessage = FormHelper::getFieldError($this->sessionFilterFieldKey);

        // make sure all session input necessary to send to twig is produced above
        FormHelper::unsetSessionVars();

        return $this->view->render(
            $response,
            $this->template,
            [
                'title' => $this->model->getTableName(),
                'insertLink' => $this->insertLink,
                'filterOpsList' => QueryBuilder::getWhereOperatorsText(),
                'filterValue' => $filterFieldValue,
                'filterErrorMessage' => $filterErrorMessage,
                'filterFormAction' => $this->indexRoute,
                'filterFieldName' => $this->sessionFilterFieldKey,
                'isFiltered' => $filterColumnsInfo,
                'resetFilterRoute' => $this->filterResetRoute,
                'updateColumn' => $this->updateColumn,
                'updatePermitted' => $this->updatePermitted,
                'updateRoute' => $this->updateRoute,
                'addDeleteColumn' => $this->addDeleteColumn,
                'deleteRoute' => $this->deleteRoute,
                'results' => $results,
                'numResults' => $numResults,
                'sortColumn' => $this->model->getOrderByColumnName(),
                'sortByAsc' => $this->model->getOrderByAsc(),
                'navigationItems' => $this->navigationItems
            ]
        );
    }

    protected function resetFilter(Response $response, string $redirectRoute)
    {
        if (isset($_SESSION[$this->sessionFilterColumnsKey])) {
            unset($_SESSION[$this->sessionFilterColumnsKey]);
        }
        if (isset($_SESSION[$this->sessionFilterValueKey])) {
            unset($_SESSION[$this->sessionFilterValueKey]);
        }
        // redirect to the clean url
        return $response->withRedirect($this->router->pathFor($redirectRoute));
    }

    // new input takes precedence over session value
    protected function getFilterFieldValue(): string
    {
        if (isset($_SESSION[SESSION_REQUEST_INPUT_KEY][$this->sessionFilterFieldKey])) {
            return $_SESSION[SESSION_REQUEST_INPUT_KEY][$this->sessionFilterFieldKey];
        } elseif (isset($_SESSION[$this->sessionFilterValueKey])) {
            return $_SESSION[$this->sessionFilterValueKey];
        } else {
            return '';
        }
    }

    public function getSessionFilterColumnsKey(): string
    {
        return $this->sessionFilterColumnsKey;
    }

    public function getSessionFilterValueKey(): string
    {
        return $this->sessionFilterValueKey;
    }

    public function getSessionFilterFieldKey(): string
    {
        return $this->sessionFilterFieldKey;
    }
}
