<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\CRUD;

class CrudHelper
{
    static public function getRouteName(string $routePrefix, string $routeType = 'index', string $actionType = null)
    {
        $validRouteTypes = ['index', 'insert', 'update', 'delete'];
        if (!in_array($routeType, $validRouteTypes)) {
            throw new \Exception("Invalid route type $routeType");
        }


        $routeName = ROUTEPREFIX_ADMIN . '.' . $routePrefix;

        if ($actionType != null) {
            $validActionTypes = ['put', 'post'];
            if (!in_array($actionType, $validActionTypes)) {
                throw new \Exception("Invalid route type $routeType");
            }
            $routeName .= '.' . $actionType;
        }

        $routeName .= '.' . $routeType;

        return $routeName;
    }
}
