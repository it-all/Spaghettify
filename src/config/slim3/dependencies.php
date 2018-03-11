<?php
declare(strict_types=1);

use Infrastructure\Security\Authentication\AuthenticationService;
use Infrastructure\Security\Authorization\AuthorizationService;
use Slim\Views\TwigExtension;
use Slim\Views\Twig;

// DIC configuration

// -----------------------------------------------------------------------------
// Services (Dependencies)
// -----------------------------------------------------------------------------

// Database
$container['database'] = function($container) use ($database) {
    return $database;
};

// Authentication
$container['authentication'] = function($container) {
    $settings = $container->get('settings');
    return new AuthenticationService($settings['authentication']['maxFailedLogins'], $settings['authentication']['adminHomeRoutes']);
};

// Authorization
$container['authorization'] = function($container) {
    $settings = $container->get('settings');
    return new AuthorizationService($settings['authorization'], $settings['adminDefaultRole']);
};

// System Events (Database Log)
$container['systemEvents'] = function($container) use ($systemEventsModel) {
    return $systemEventsModel;
};

// Twig
$container['view'] = function ($container) {
    $settings = $container->get('settings');
    $view = new Twig($settings['view']['paths'], [
        'cache' => $settings['view']['pathCache'],
        'auto_reload' => $settings['view']['autoReload'],
        'debug' => $settings['view']['debug']
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new TwigExtension($container->router, $basePath));

    if ($settings['view']['debug']) {
        // allows {{ dump(var) }}
        $view->addExtension(new Twig_Extension_Debug());
    }

    // make authentication class available inside templates
    $view->getEnvironment()->addGlobal('authentication', [
        'check' => $container->authentication->check(),
        'user' => $container->authentication->user()
    ]);

    if (isset($_SESSION[SESSION_ADMIN_NOTICE])) {
        $view->getEnvironment()->addGlobal('adminNotice', $_SESSION[SESSION_ADMIN_NOTICE]);
        unset($_SESSION[SESSION_ADMIN_NOTICE]);
    }

    // frontend
    if (isset($_SESSION[SESSION_NOTICE])) {
        $view->getEnvironment()->addGlobal('notice', $_SESSION[SESSION_NOTICE]);
        unset($_SESSION[SESSION_NOTICE]);
    }

    // make some config settings available inside templates
    $view->getEnvironment()->addGlobal('isLive', $settings['isLive']);
    $view->getEnvironment()->addGlobal('businessName', $settings['businessName']);
    $businessDba = (mb_strlen($settings['businessDba']) > 0) ? $settings['businessDba'] : $settings['businessName'];
    $view->getEnvironment()->addGlobal('businessDba', $businessDba);

    // allow access to current script name inside templates
    $view->getEnvironment()->addGlobal('currentUri', $container['request']->getUri()->getPath());

    return $view;
};

// Mailer
$container['mailer'] = function($container) {
    $settings = $container->get('settings');
    return $settings['mailer'];
};

// Form Validation
$container['validator'] = function ($container) {
    return new \Infrastructure\Utilities\ValitronValidatorExtension();
};

// CSRF
$container['csrf'] = function ($container) {
    $storage = null; // cannot directly pass null because received by reference.
    // setting the persistentTokenMode parameter true allows redisplaying a form with errors with a render rather than redirect call and will not cause CSRF failure if the page is refreshed (http://blog.ircmaxell.com/2013/02/preventing-csrf-attacks.html)
    $guard = new \Slim\Csrf\Guard('csrf', $storage, null, 200, 16, true);
    $guard->setFailureCallable(function ($request, $response, $next) {
        $request = $request->withAttribute("csrf_status", false);
        return $next($request, $response);
    });
    return $guard;
};
