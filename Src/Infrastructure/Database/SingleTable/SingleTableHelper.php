<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\SingleTable;

use It_All\Spaghettify\Src\Infrastructure\Database\SingleTable\SingleTableModel;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Response;

class SingleTableHelper
{
    public static function updateNoRecord(Container $container, Response $response, $primaryKey, SingleTableModel $model, string $routePrefix)
    {
        $eventNote = $model->getPrimaryKeyColumnName().":$primaryKey|Table: ".$model->getTableName();
        $container->systemEvents->insertWarning('Record not found for update', (int) $container->authentication->getUserId(), $eventNote);
        $_SESSION[SESSION_ADMIN_NOTICE] = ["Record $primaryKey Not Found", 'adminNoticeFailure'];
        return $response->withRedirect($container->router->pathFor(getRouteName(true, $routePrefix, 'index')));
    }

}
