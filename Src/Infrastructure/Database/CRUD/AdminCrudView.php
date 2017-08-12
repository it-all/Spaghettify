<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Database\CRUD;

use It_All\Spaghettify\Src\Infrastructure\AdminView;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class AdminCrudView extends AdminView
{
    protected $routePrefix;
    protected $model;

    public function __construct(
        Container $container,
        DatabaseTableModel $model,
        string $routePrefix
    )
    {
        $this->model = $model;
        $this->routePrefix = $routePrefix;
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, $args)
    {
        $this->indexView($response);
    }

    public function getInsert(Request $request, Response $response, $args)
    {
        return $this->insertView($response);
    }

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

        /**
         * data to send to FormHelper - either from the model or from prior input. Note that when sending null FormHelper defaults to using $_SESSION['formInput']. It's important to send null, not $_SESSION['formInput'], because FormHelper unsets $_SESSION['formInput'] after using it.
         * note, this works for post/put because controller calls this method directly in case of errors instead of redirecting
         */
        $fieldData = ($request->isGet()) ? $record : null;

        return $this->updateView($request, $response, $args, $fieldData);
    }

    protected function indexView(Response $response, string $columns = '*')
    {
        if ($results = pg_fetch_all($this->model->select($columns, 'PRIMARYKEY', false))) {
            $numResults = count($results);
        } else {
            $numResults = 0;
        }

        $insertLink = ($this->authorization->check($this->container->settings['authorization'][$this->routePrefix.'.insert'])) ? ['text' => 'Insert '.$this->model->getFormalTableName(false), 'route' => $this->routePrefix.'.insert'] : false;

        return $this->view->render(
            $response,
            'admin/list.twig',
            [
                'title' => $this->model->getFormalTableName(),
                'primaryKeyColumn' => $this->model->getPrimaryKeyColumnName(),
                'insertLink' => $insertLink,
                'updatePermitted' => $this->authorization
                    ->check($this->container->settings['authorization'][$this->routePrefix.'.update']),
                'updateRoute' => $this->routePrefix.'.put.update',
                'addDeleteColumn' => true,
                'deleteRoute' => $this->routePrefix.'.delete',
                'results' => $results,
                'numResults' => $numResults,
                'sortColumn' => $this->model->getPrimaryKeyColumnName(),
                'sortByAsc' => false,
                'navigationItems' => $this->navigationItems
            ]
        );
    }

    protected function insertView(Response $response)
    {
        $form = new DatabaseTableForm($this->model, $this->routePrefix.'.post.insert', $this->csrf->getTokenNameKey(), $this->csrf->getTokenName(), $this->csrf->getTokenValueKey(), $this->csrf->getTokenValue());

        return $this->view->render(
            $response,
            'admin/form.twig',
            [
                'title' => 'Insert '. $this->model->getFormalTableName(false),
                'form' => $form,
                'navigationItems' => $this->navigationItems
            ]
        );
    }

    protected function updateView(Request $request, Response $response, $args, $fieldData = null)
    {
        $form = new DatabaseTableForm($this->model, 'update', $fieldData);

        return $this->view->render(
            $response,
            'admin/form.twig',
            [
                'title' => 'Update ' . $this->model->getFormalTableName(false),
                'formActionRoute' => $this->routePrefix.'.put.update',
                'primaryKey' => $args['primaryKey'],
                'formFields' => $form->getFields(),
                'focusField' => $form->getFocusField(),
                'generalFormError' => $form->getGeneralError(),
                'navigationItems' => $this->navigationItems
            ]
        );
    }
}
