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
    public function getWhereFilterColumns(string $whereFieldValue): ?array
    {
        $whereColumnsInfo = [];
        $whereParts = explode(",", $whereFieldValue);
        if (strlen($whereParts[0]) == 0) {
            FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => 'Not Entered']);
            return null;
        } else {

            foreach ($whereParts as $whereFieldOperatorValue) {
                //field:operator:value
                $whereFieldOperatorValueParts = explode(":", $whereFieldOperatorValue);
                if (count($whereFieldOperatorValueParts) != 3) {
                    FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => 'Malformed']);
                    return null;
                }
                $columnName = trim($whereFieldOperatorValueParts[0]);
                $whereOperator = strtoupper(trim($whereFieldOperatorValueParts[1]));
                $whereValue = trim($whereFieldOperatorValueParts[2]);

                // validate the column name
                try {
                    $columnNameSql = $this->model::getColumnNameSqlForColumnName($columnName);
                } catch (\Exception $e) {
                    FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => "$columnName not found"]);
                    return null;
                }

                // validate the operator
                if (!QueryBuilder::validateWhereOperator($whereOperator)) {
                    FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => "Invalid Operator $whereOperator"]);
                    return null;
                }

                // null value only valid with IS and IS NOT operators
                if (strtolower($whereValue) == 'null') {
                    if ($whereOperator != 'IS' && $whereOperator != 'IS NOT') {
                        FormHelper::setFieldErrors([$this->view::SESSION_WHERE_FIELD_NAME => "Mismatched null, $whereOperator"]);
                        return null;
                    }
                    $whereValue = null;
                }

                if (!isset($whereColumnsInfo[$columnNameSql])) {
                    $whereColumnsInfo[$columnNameSql] = [
                        'operators' => [$whereOperator],
                        'values' => [$whereValue]
                    ];
                } else {
                    $whereColumnsInfo[$columnNameSql]['operators'][] = $whereOperator;
                    $whereColumnsInfo[$columnNameSql]['values'][] = $whereValue;
                }
            }
        }

        return $whereColumnsInfo;
    }

    public function postIndexFilter(Request $request, Response $response, $args)
    {
        $this->setRequestInput($request);

        if (!isset($_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_WHERE_FIELD_NAME])) {
            throw new \Exception("where session input must be set");
        }

        if (!$whereColumnsInfo = $this->getWhereFilterColumns($_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_WHERE_FIELD_NAME], $this->model)) {
            // redisplay form with error
            return $this->view->indexView($response);
        } else {
            $_SESSION[$this->view::SESSION_WHERE_COLUMNS] = $whereColumnsInfo;
            $_SESSION[$this->view::SESSION_WHERE_VALUE_KEY] = $_SESSION[SESSION_REQUEST_INPUT_KEY][$this->view::SESSION_WHERE_FIELD_NAME];
            FormHelper::unsetSessionVars();
            return $response->withRedirect($this->router->pathFor(ROUTE_SYSTEM_EVENTS));
        }
    }

}
