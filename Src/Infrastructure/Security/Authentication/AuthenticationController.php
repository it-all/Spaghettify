<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\Spaghettify\Src\Infrastructure\Controller;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Form;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthenticationController extends Controller
{
    function postLogin(Request $request, Response $response, $args)
    {
        $_SESSION['formInput'] = $request->getParsedBody();

        $form = new Form($this->authentication->getLoginFields());

        if (!$this->validator->validate(
                $request->getParsedBody(),
                $form->getValidationRules())
        ) {
            // redisplay the form with input values and error(s)
            $av = new AuthenticationView($this->container);
            return $av->getLogin($request, $response, $args);
        }

        if ($this->authentication->attemptLogin(
            $request->getParam('username'),
            $request->getParam('password')
        )) {
            unset($_SESSION['formInput']);
            $this->logger->addInfo($request->getParam('username').' logged in');

            // redirect to proper resource
            if (isset($_SESSION['gotoAdminPage'])) {
                $redirect = $_SESSION['gotoAdminPage'];
                unset($_SESSION['gotoAdminPage']);
            } else {
                $redirect = $this->router->pathFor('admin.home');
            }

            return $response->withRedirect($redirect);
        }

        $this->logger->addWarning('Unsuccessful login for username: '.
            $request->getParam('username') . ' from IP: '. $_SERVER['REMOTE_ADDR']);

        if ($this->authentication->tooManyFailedLogins()) {
            $errorMessage = $this->authentication->getNumFailedLogins() . ' unsuccessful login attempts for IP: ' . $_SERVER['REMOTE_ADDR'];
            $this->logger->addWarning($errorMessage);
            throw new \Exception($errorMessage);
        }

        // redisplay the form with input values and error(s)
        $_SESSION['generalFormError'] = 'Login Unsuccessful';
        return $response->withRedirect($this->router->pathFor('authentication.login'));
    }

    public function getLogout(Request $request, Response $response)
    {
        $this->logger->addInfo($_SESSION['user']['username'].' logged out');
        $this->authentication->logout();
        return $response->withRedirect($this->router->pathFor('home'));
    }
}
