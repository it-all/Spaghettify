<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\Spaghettify\Src\Infrastructure\Middleware;
use It_All\Spaghettify\Src\Spaghettify;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthenticationMiddleware extends Middleware
{
	public function __invoke(Request $request, Response $response, $next)
	{
		// check if the user is not signed in
		if (!$this->container->authentication->check()) {
            $this->container->systemEvents->insertWarning('Login required to access resource');
            $_SESSION[Spaghettify::SESSION_ADMIN_NOTICE] = ["Login required", 'adminNoticeFailure'];
            $_SESSION[Spaghettify::SESSION_GOTO_ADMIN_PATH] = $request->getUri()->getPath();
            return $response->withRedirect($this->container->router->pathFor(ROUTE_LOGIN));
		}

		$response = $next($request, $response);
		return $response;
	}
}
