<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure;

use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class ListView extends AdminView
{
    protected $sessionFilterColumnsKey;
    protected $sessionFilterValueKey;
    protected $sessionFilterFieldKey;

    public function __construct(Container $container, string $sessionFilterColumnsKey, string $sessionFilterValueKey, string $sessionFilterFieldKey)
    {
        $this->sessionFilterColumnsKey = $sessionFilterColumnsKey;
        $this->sessionFilterValueKey = $sessionFilterValueKey;
        $this->sessionFilterFieldKey = $sessionFilterFieldKey;
        parent::__construct($container);
    }

    public function index(Request $request, Response $response, $args)
    {
        return $this->indexView($response);
    }

    public function indexResetFilter(Request $request, Response $response, $args)
    {
        // redirect to the clean url
        return $this->indexView($response, true);
    }

    protected function resetFilter(Response $response, string $redirectRoute)
    {
        if (isset($_SESSION[$this->sessionFilterColumnsKey])) {
            unset($_SESSION[$this->sessionFilterColumnsKey]);
        }
        if (isset($_SESSION[$this->sessionFilterValueKey])) {
            unset($_SESSION[$this->sessionFilterValueKey]);
        }
        // redirect to the clean url
        return $response->withRedirect($this->router->pathFor($redirectRoute));
    }

    // new input takes precedence over session value
    protected function getFilterFieldValue(): string
    {
        if (isset($_SESSION[SESSION_REQUEST_INPUT_KEY][$this->sessionFilterFieldKey])) {
            return $_SESSION[SESSION_REQUEST_INPUT_KEY][$this->sessionFilterFieldKey];
        } elseif (isset($_SESSION[$this->sessionFilterValueKey])) {
            return $_SESSION[$this->sessionFilterValueKey];
        } else {
            return '';
        }
    }

    public function getSessionFilterColumnsKey(): string
    {
        return $this->sessionFilterColumnsKey;
    }

    public function getSessionFilterValueKey(): string
    {
        return $this->sessionFilterValueKey;
    }

    public function getSessionFilterFieldKey(): string
    {
        return $this->sessionFilterFieldKey;
    }
}
