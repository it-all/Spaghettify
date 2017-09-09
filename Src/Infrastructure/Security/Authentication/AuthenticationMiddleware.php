<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authentication;

use It_All\Spaghettify\Src\Infrastructure\Middleware;

class AuthenticationMiddleware extends Middleware
{
	public function __invoke($request, $response, $next)
	{
		// check if the user is not signed in
		if (!$this->container->authentication->check()) {
            $this->container->logger->addWarning('Login required to access resource: ' .
                $request->getUri()->getPath() . ' for IP: ' . $_SERVER['REMOTE_ADDR']);
            $_SESSION[SESSION_ADMIN_NOTICE] = ["Login required", 'adminNoticeFailure'];
            $_SESSION[SESSION_GOTO_ADMIN_PATH] = $request->getUri()->getPath();
            return $response->withRedirect($this->container->router->pathFor(ROUTE_LOGIN));
		}

		$response = $next($request, $response);
		return $response;
	}
}
