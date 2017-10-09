<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\SystemEvents;

use It_All\Spaghettify\Src\Infrastructure\Controller;
use It_All\Spaghettify\Src\Infrastructure\Database\Queries\QueryBuilder;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class SystemEventsController extends Controller
{
    private $view;
    private $model;

    public function __construct(Container $container)
    {
        $this->view = new SystemEventsView($container);
        $this->model = new SystemEventsModel();
        parent::__construct($container);
    }

    // parse the where field
    public function getFilterColumns(string $filterFieldValue): ?array
    {
        $filterColumnsInfo = [];
        $filterParts = explode(",", $filterFieldValue);
        if (strlen($filterParts[0]) == 0) {
            FormHelper::setFieldErrors([$this->view::SESSION_FILTER_FIELD_NAME => 'Not Entered']);
            return null;
        } else {

            foreach ($filterParts as $whereFieldOperatorValue) {
                //field:operator:value
                $whereFieldOperatorValueParts = explode(":", $whereFieldOperatorValue);
                if (count($whereFieldOperatorValueParts) != 3) {
                    FormHelper::setFieldErrors([$this->view::SESSION_FILTER_FIELD_NAME => 'Malformed']);
                    return null;
                }
                $columnName = trim($whereFieldOperatorValueParts[0]);
                $whereOperator = strtoupper(trim($whereFieldOperatorValueParts[1]));
                $whereValue = trim($whereFieldOperatorValueParts[2]);

                // validate the column name
                try {
                    $columnNameSql = $this->model::getColumnNameSqlForColumnName($columnName);
                } catch (\Exception $e) {
                    FormHelper::setFieldErrors([$this->view::SESSION_FILTER_FIELD_NAME => "$columnName not found"]);
                    return null;
                }

                // validate the operator
                if (!QueryBuilder::validateWhereOperator($whereOperator)) {
                    FormHelper::setFieldErrors([$this->view::SESSION_FILTER_FIELD_NAME => "Invalid Operator $whereOperator"]);
                    return null;
                }

                // null value only valid with IS and IS NOT operators
                if (strtolower($whereValue) == 'null') {
                    if ($whereOperator != 'IS' && $whereOperator != 'IS NOT') {
                        FormHelper::setFieldErrors([$this->view::SESSION_FILTER_FIELD_NAME => "Mismatched null, $whereOperator"]);
                        return null;
                    }
                    $whereValue = null;
                }

                if (!isset($filterColumnsInfo[$columnNameSql])) {
                    $filterColumnsInfo[$columnNameSql] = [
                        'operators' => [$whereOperator],
                        'values' => [$whereValue]
                    ];
                } else {
                    $filterColumnsInfo[$columnNameSql]['operators'][] = $whereOperator;
                    $filterColumnsInfo[$columnNameSql]['values'][] = $whereValue;
                }
            }
        }

        return $filterColumnsInfo;
    }

    public function postIndexFilter(Request $request, Response $response, $args)
    {
        $this->setRequestInput($request);

        if (!isset($_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_FILTER_FIELD_NAME])) {
            throw new \Exception("session filter input must be set");
        }

        if (!$filterColumnsInfo = $this->getFilterColumns($_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_FILTER_FIELD_NAME])) {
            // redisplay form with error
            return $this->view->indexView($response);
        } else {
            $_SESSION[$this->view::SESSION_FILTER_COLUMNS] = $filterColumnsInfo;
            $_SESSION[$this->view::SESSION_FILTER_VALUE_KEY] = $_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_FILTER_FIELD_NAME];
            FormHelper::unsetSessionVars();
            return $response->withRedirect($this->router->pathFor(ROUTE_SYSTEM_EVENTS));
        }
    }

}
