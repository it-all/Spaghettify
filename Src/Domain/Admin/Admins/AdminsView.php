<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins;

use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\AdminCrudView;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AdminsView extends AdminCrudView
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new AdminsModel(), 'admins');
    }

    /**
     * override to eliminate some columns
     * @param $request
     * @param $response
     * @param $args
     */
    public function index(Request $request, Response $response, $args)
    {
        $this->indexView($response, 'id, name, username');
    }

    /**
     * override to leave pw field blank
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function getUpdate(Request $request, Response $response, $args)
    {
        // make sure there is a record for the model
        if (!$record = $this->model->selectForPrimaryKey($args['primaryKey'])) {
            $_SESSION['adminNotice'] = [
                "Record ".$args['primaryKey']." Not Found",
                'adminNoticeFailure'
            ];
            return $response->withRedirect($this->router->pathFor($this->routePrefix.'.index'));
        }

        $record['password_hash'] = '';

        /**
         * data to send to FormHelper - either from the model or from prior input. Note that when sending null FormHelper defaults to using $_SESSION[SESSION_REQUEST_INPUT_KEY]. It's important to send null, not $_SESSION['formInput'], because FormHelper unsets $_SESSION[SESSION_REQUEST_INPUT_KEY] after using it.
         * note, this works for post/put because controller calls this method directly in case of errors instead of redirecting
         */
        $fieldData = ($request->isGet()) ? $record : null;

        return $this->updateView($request, $response, $args, $fieldData);
    }
}
