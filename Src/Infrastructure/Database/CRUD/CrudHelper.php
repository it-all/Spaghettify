<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\CRUD;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use It_All\Spaghettify\Src\Spaghettify;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class CrudHelper
{
    public static function updateNoRecord(Container $container, Response $response, $primaryKey, DatabaseTableModel $model, string $routePrefix)
    {
        $eventNote = $model->getPrimaryKeyColumnName().":$primaryKey|Table: ".$model->getTableName();
        $container->systemEvents->insertWarning('Record not found for update', (int) $container->authentication->getUserId(), $eventNote);
        $_SESSION[Spaghettify::SESSION_ADMIN_NOTICE] = ["Record $primaryKey Not Found", 'adminNoticeFailure'];
        return $response->withRedirect($container->router->pathFor(getRouteName(true, $routePrefix, 'index')));
    }

}
