<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\Spaghettify\Src\Infrastructure\Controller;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthenticationController extends Controller
{
    function postLogin(Request $request, Response $response, $args)
    {
        $this->setRequestInput($request);

        $this->validator = $this->validator->withData($_SESSION[SESSION_REQUEST_INPUT_KEY], $this->authentication->getLoginFields());

        $this->validator->rules($this->authentication->getLoginFieldValidationRules());

        if (!$this->validator->validate()) {
            // redisplay the form with input values and error(s)
            FormHelper::setFieldErrors($this->validator->getFirstErrors());
            $av = new AuthenticationView($this->container);
            return $av->getLogin($request, $response, $args);
        }

        if (!$this->authentication->attemptLogin(
            $request->getParam('username'),
            $request->getParam('password_hash')
        )) {
            $this->logger->addWarning('Unsuccessful login for username: '.
                $request->getParam('username') . ' from IP: '. $_SERVER['REMOTE_ADDR']);

            if ($this->authentication->tooManyFailedLogins()) {
                $errorMessage = $this->authentication->getNumFailedLogins() . ' unsuccessful login attempts for IP: ' . $_SERVER['REMOTE_ADDR'];
                $this->logger->addWarning($errorMessage);
                throw new \Exception($errorMessage);
            }

            FormHelper::setGeneralError('Login Unsuccessful');

            // redisplay the form with input values and error(s)
            return $response->withRedirect($this->router->pathFor(ROUTE_LOGIN));

        }

        // successful login
        FormHelper::unsetSessionVars();
        $this->logger->addInfo($request->getParam('username').' logged in');

        // redirect to proper resource
        if (isset($_SESSION[SESSION_GOTO_ADMIN_PATH])) {
            $redirect = $_SESSION[SESSION_GOTO_ADMIN_PATH];
            unset($_SESSION[SESSION_GOTO_ADMIN_PATH]);
        } else {
            $redirect = $this->router->pathFor($this->authentication->getAdminHomeRouteForUser());
        }

        return $response->withRedirect($redirect);
    }

    public function getLogout(Request $request, Response $response)
    {
        if (!$username = $this->authentication->getUserUsername()) {

            $this->logger->addWarning('Attempted logout for non-logged-in visitor from IP: '. $_SERVER['REMOTE_ADDR']);

        } else {

            $this->logger->addInfo($username.' logged out');
            $this->authentication->logout();
        }

        return $response->withRedirect($this->router->pathFor(ROUTE_HOME));
    }
}
