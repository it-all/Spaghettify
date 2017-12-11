<?php
declare(strict_types=1);

// GLOBAL CONSTANTS

define('DOMAIN_NAME', 'it-all.com');

// routes
// KISS - don't use array constants because may be tricky to access in twig
define('ROUTE_HOME', 'home');
define('ROUTE_PAGE_NOT_FOUND', 'pageNotFound');
define('ROUTE_LOGIN', 'authentication.login');
define('ROUTE_LOGIN_POST', 'authentication.post.login');

// admin route prefixes
define('ROUTEPREFIX_ADMIN', 'admin');
define('ROUTEPREFIX_ADMIN_ADMINISTRATORS', 'administrators');
define('ROUTEPREFIX_ADMIN_ROLES', 'roles');
define('ROUTEPREFIX_ADMIN_TESTIMONIALS', 'testimonials');

// admin routes
define('ROUTE_ADMIN_HOME_DEFAULT', 'admin.home');
define('ROUTE_LOGOUT', 'authentication.logout');
// login attempts
define('ROUTE_LOGIN_ATTEMPTS', ROUTEPREFIX_ADMIN.'.logins.index');
define('ROUTE_LOGIN_ATTEMPTS_RESET', ROUTEPREFIX_ADMIN.'.logins.index.reset');
// system events
define('ROUTE_SYSTEM_EVENTS', ROUTEPREFIX_ADMIN.'.systemEvents.index');
define('ROUTE_SYSTEM_EVENTS_RESET', ROUTEPREFIX_ADMIN.'.systemEvents.index.reset');
// administrators
define('ROUTE_ADMIN_ADMINISTRATORS', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINISTRATORS.'.index');
define('ROUTE_ADMIN_ADMINISTRATORS_RESET', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINISTRATORS.'.index.reset');
define('ROUTE_ADMIN_ADMINISTRATORS_INSERT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINISTRATORS.'.insert');
define('ROUTE_ADMIN_ADMINISTRATORS_INSERT_POST', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINISTRATORS.'.post.insert');
define('ROUTE_ADMIN_ADMINISTRATORS_UPDATE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINISTRATORS.'.update');
define('ROUTE_ADMIN_ADMINISTRATORS_UPDATE_PUT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINISTRATORS.'.put.update');
define('ROUTE_ADMIN_ADMINISTRATORS_DELETE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINISTRATORS.'.delete');
// roles
define('ROUTE_ADMIN_ROLES', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.index');
define('ROUTE_ADMIN_ROLES_RESET', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.index.reset');
define('ROUTE_ADMIN_ROLES_INSERT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.insert');
define('ROUTE_ADMIN_ROLES_INSERT_POST', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.post.insert');
define('ROUTE_ADMIN_ROLES_UPDATE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.update');
define('ROUTE_ADMIN_ROLES_UPDATE_PUT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.put.update');
define('ROUTE_ADMIN_ROLES_DELETE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.delete');
// testimonials
define('ROUTE_ADMIN_TESTIMONIALS', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.index');
define('ROUTE_ADMIN_TESTIMONIALS_RESET', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.index.reset');
define('ROUTE_ADMIN_TESTIMONIALS_INSERT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.insert');
define('ROUTE_ADMIN_TESTIMONIALS_INSERT_POST', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.post.insert');
define('ROUTE_ADMIN_TESTIMONIALS_UPDATE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.update');
define('ROUTE_ADMIN_TESTIMONIALS_UPDATE_PUT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.put.update');
define('ROUTE_ADMIN_TESTIMONIALS_DELETE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.delete');
// end routes

// nav / permission options without routes
define('NAV_ADMIN_SYSTEM', ROUTEPREFIX_ADMIN.'.'.'system');
define('NAV_ADMIN_MARKETING', ROUTEPREFIX_ADMIN.'.'.'marketing');
define('NAV_ADMIN_TESTIMONIALS', ROUTEPREFIX_ADMIN.'.'.'testimonials');

// $_SESSION var keys
define('SESSION_REQUEST_INPUT_KEY', 'requestInput');
define('SESSION_NUMBER_FAILED_LOGINS', 'numFailedLogins');
define('SESSION_LAST_ACTIVITY', 'lastActivity');
define('SESSION_USER', 'user');
define('SESSION_USER_ID', 'id');
define('SESSION_USER_NAME', 'name');
define('SESSION_USER_USERNAME', 'username');
define('SESSION_USER_ROLE', 'role');
define('SESSION_ADMIN_NOTICE', 'adminNotice');
define('SESSION_NOTICE', 'notice');
define('SESSION_GOTO_ADMIN_PATH', 'gotoAdminPath');

// END GLOBAL CONSTANTS

return [

    'businessName' => 'Spaghettify',

    'businessDba' => '',

    'domainName' => DOMAIN_NAME,

    'isLive' => true,

    'hostName' => DOMAIN_NAME,

    'domainUseWww' => false,

    'session' => [
        'ttlHours' => 24,
        'savePath' => __DIR__ . '/../../storage/sessions',
    ],

    'storage' => [
        'logs' => [
            'pathPhpErrors' => __DIR__ . '/../../storage/logs/phpErrors.log'
        ],

        'cache' => [
            'pathCache' => __DIR__ . '/../../storage/cache/',
            'routerCacheFile' => __DIR__ . '/../../storage/cache/router.txt'
        ]
    ],

    'pathTemplates' => __DIR__ . '/../templates/',
    'pathTwigMacros' => __DIR__ . '/../../vendor/it-all/form-former/src/twigMacros',

    'errors' => [
        'emailTo' => ['owner', 'programmer'], // emails must be set in 'emails' section
        'fatalMessage' => 'Apologies, there has been an error on our site. We have been alerted and will correct it as soon as possible.',
        'logToDatabase' => true,
        'echoDev' => true, // echo on dev servers (note, live server will never echo)
        'emailDev' => false // email on dev servers (note, live server will always email)
    ],

    'emails' => [
        'owner' => "owner@".DOMAIN_NAME,
        'programmer' => "programmer@".DOMAIN_NAME,
        'service' => "service@".DOMAIN_NAME
    ],

    // If exceeded in a session, will insert a system event and disallow further login attempts
    'maxFailedLogins' => 5,

    // Removes leading and trailing blank space on all inputs
    'trimAllUserInput' => true,


    /* Either functionalityCategory => permissions or functionalityCategory.functionality => permissions where permissions is either a string equal to the minimum authorized role or an array of authorized roles */
    // Important to properly match the indexes to routes authorization
    // The role values must be in the database: roles.role
    // If the index is not defined for a route or nav section, no authorization check is performed (all administrators (logged in users) will be able to access resource or view nav section). therefore, indexes only need to be defined for routes and nav sections that require authorization greater than the base (least permission) role.
    // Note also that it's possible to give a role access to a resource, but then hide the navigation to to that resource to that role, which would usually be undesirable. For example, below the bookkeeper is authorized to view System Events, but will not see the System nav section because of the NAV_ADMIN_SYSTEM entry permissions being set to 'owner'
    'administratorPermissions' => [
        ROUTE_LOGIN_ATTEMPTS => 'director',
        ROUTE_SYSTEM_EVENTS => ['owner', 'bookkeeper'],
        ROUTE_ADMIN_ADMINISTRATORS => 'director',
        ROUTE_ADMIN_ADMINISTRATORS_RESET => 'director',
        ROUTE_ADMIN_ADMINISTRATORS_INSERT => 'owner',
        ROUTE_ADMIN_ADMINISTRATORS_UPDATE => 'owner',
        ROUTE_ADMIN_ADMINISTRATORS_DELETE => 'owner',
        ROUTE_ADMIN_ROLES => 'owner',
        NAV_ADMIN_SYSTEM => 'owner',
    ],

    // Set the admin home route for users or roles
    'adminHomeRoutes' => [
        'usernames' => [],
        'roles' => [
            'owner' => ROUTE_SYSTEM_EVENTS
        ]
    ],

    // When entering a new admin, role will default to this
    'adminDefaultRole' => 'user'

];
