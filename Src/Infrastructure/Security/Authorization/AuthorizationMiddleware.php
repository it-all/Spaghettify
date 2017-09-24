<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security\Authorization;

use It_All\Spaghettify\Src\Infrastructure\Middleware;
use It_All\Spaghettify\Src\Spaghettify;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class AuthorizationMiddleware extends Middleware
{
    private $minimumRole;

    public function __construct(Container $container, string $minimumRole)
    {
        $this->minimumRole = $minimumRole;
        parent::__construct($container);
    }

    public function __invoke(Request $request, Response $response, $next)
	{
        // check if the user is not authorized
        if (!$this->container->authorization->check($this->minimumRole)) {

            $this->container->systemEvents->insertAlert('No authorization for resource', $this->container->authentication->getUserId());

            $_SESSION[SESSION_ADMIN_NOTICE] = ['No permission', 'adminNoticeFailure'];

            return $response->withRedirect($this->container->router->pathFor(ROUTE_ADMIN_HOME_DEFAULT));
        }

		$response = $next($request, $response);
		return $response;
	}
}