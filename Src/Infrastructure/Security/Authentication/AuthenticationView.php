<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\Spaghettify\Src\Infrastructure\AdminView;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\Form;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthenticationView extends AdminView
{
    public function getLogin(Request $request, Response $response, $args)
    {
        if ($this->authentication->tooManyFailedLogins()) {
            return $response->withRedirect($this->router->pathFor(ROUTE_HOME));
        }

        $form = $this->authentication->getForm($this->csrf->getTokenNameKey(), $this->csrf->getTokenName(), $this->csrf->getTokenValueKey(), $this->csrf->getTokenValue(), $this->router->pathFor(ROUTE_LOGIN_POST));

        FormHelper::unsetSessionVars();

        // render page
        return $this->view->render(
            $response,
            'admin/authentication/login.twig',
            [
                'title' => '::Login',
                'form' => $form
            ]
        );
    }
}
