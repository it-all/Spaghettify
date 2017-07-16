<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\Spaghettify\Src\Infrastructure\AdminView;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Form;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthenticationView extends AdminView
{
    public function getLogin(Request $request, Response $response, $args)
    {
        if ($this->authentication->tooManyFailedLogins()) {
            return $response->withRedirect($this->router->pathFor('home'));
        }

        $form = new Form($this->authentication->getLoginFields());
        $form->insertValuesErrors();

        // render page
        return $this->view->render(
            $response,
            'admin/authentication/login.twig',
            [
                'title' => '::Login',
                'focusField' => $this->authentication->getFocusField(),
                'formFields' => $form->getFields(),
                'generalFormError' => $form->getGeneralError()
            ]
        );
    }
}
