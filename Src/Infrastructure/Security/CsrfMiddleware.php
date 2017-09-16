<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure\Security;

use It_All\Spaghettify\Src\Infrastructure\Middleware;
use Slim\Http\Request;
use Slim\Http\Response;

class CsrfMiddleware extends Middleware
{
	public function __invoke(Request $request, Response $response, $next)
	{
        if (false === $request->getAttribute('csrf_status')) {
            $eventTitle = 'CSRF Check Failure';
            $this->container->systemEvents->insertWarning($eventTitle, (int) $this->container->authentication->getUserId());
            throw new \Exception($eventTitle);
        }

        $this->container->view->getEnvironment()->addGlobal('csrf', [
            'tokenNameKey' => $this->container->csrf->getTokenNameKey(),
            'tokenName' => $this->container->csrf->getTokenName(),
            'tokenValueKey' => $this->container->csrf->getTokenValueKey(),
            'tokenValue' => $this->container->csrf->getTokenValue()
        ]);

		$response = $next($request, $response);
		return $response;
	}
}
