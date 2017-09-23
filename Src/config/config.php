<?php
declare(strict_types=1);

$domainName = 'it-all.com';

// routes
// KISS - don't use array constants because may be tricky to access in twig
define('ROUTE_HOME', 'home');
define('ROUTE_PAGE_NOT_FOUND', 'pageNotFound');
define('ROUTE_LOGIN', 'authentication.login');
define('ROUTE_LOGIN_POST', 'authentication.post.login');

// admin route prefixes
define('ROUTEPREFIX_ADMIN', 'admin');
define('ROUTEPREFIX_ADMIN_ADMINS', 'admins');
define('ROUTEPREFIX_ADMIN_ROLES', 'roles');
define('ROUTEPREFIX_ADMIN_TESTIMONIALS', 'testimonials');

// admin routes
define('ROUTE_ADMIN_HOME_DEFAULT', 'admin.home');
define('ROUTE_LOGOUT', 'authentication.logout');
// login attempts
define('ROUTE_LOGIN_ATTEMPTS', ROUTEPREFIX_ADMIN.'.logins.index');
// system events
define('ROUTE_SYSTEM_EVENTS', ROUTEPREFIX_ADMIN.'.systemEvents.index');
// admins
define('ROUTE_ADMIN_ADMINS', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.index');
define('ROUTE_ADMIN_ADMINS_INSERT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.insert');
define('ROUTE_ADMIN_ADMINS_INSERT_POST', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.post.insert');
define('ROUTE_ADMIN_ADMINS_UPDATE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.update');
define('ROUTE_ADMIN_ADMINS_UPDATE_PUT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.put.update');
define('ROUTE_ADMIN_ADMINS_DELETE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ADMINS.'.delete');
// roles
define('ROUTE_ADMIN_ROLES', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.index');
define('ROUTE_ADMIN_ROLES_INSERT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.insert');
define('ROUTE_ADMIN_ROLES_INSERT_POST', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.post.insert');
define('ROUTE_ADMIN_ROLES_UPDATE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.update');
define('ROUTE_ADMIN_ROLES_UPDATE_PUT', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.put.update');
define('ROUTE_ADMIN_ROLES_DELETE', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_ROLES.'.delete');
// testimonials
define('ROUTE_ADMIN_TESTIMONIALS', ROUTEPREFIX_ADMIN.'.'.ROUTEPREFIX_ADMIN_TESTIMONIALS.'.index');
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

return [

    'businessName' => 'Spaghettify',

    'domainName' => $domainName,

    'isLive' => true,

    'hostName' => $domainName,

    'domainUseWww' => false,

    'session' => [
        'ttlHours' => 24,
        'savePath' => APP_ROOT . '../storage/sessions',
    ],

    'storage' => [
        'logs' => [
            'pathPhpErrors' => APP_ROOT . '../storage/logs/phpErrors.log',
            'pathEvents' => APP_ROOT . '../storage/logs/events.log'
        ],

        'cache' => [
            'pathCache' => APP_ROOT . '../storage/cache/',
            'routerCacheFile' => APP_ROOT . '../storage/cache/router.txt'
        ]
    ],

    'pathTemplates' => APP_ROOT . '../templates/',
    'pathTwigMacros' => VENDOR_DIR . '/it-all/form-former/src/twigMacros',

    'errors' => [
        'emailTo' => ['owner', 'programmer'], // emails must be set in 'emails' section
        'fatalMessage' => 'Apologies, there has been an error on our site. We have been alerted and will correct it as soon as possible.',
        'logToDatabase' => true,
        'echoDev' => true, // echo on dev servers (note, live server will never echo)
        'emailDev' => false // email on dev servers (note, live server will always email)
    ],

    'emails' => [
        'owner' => "owner@$domainName",
        'programmer' => "programmer@$domainName",
        'service' => "service@$domainName"
    ],

    // either functionalityCategory => minimumRole or functionalityCategory.functionality => minimum role
    // important to properly match the indexes to routes authorization
    // the role values must be in the database: roles.role
    // if the index is not defined for a route or nav section, no authorization check is performed (all admins (logged in users) will be able to access resource or view nav section). therefore, indexes only need to be defined for routes and nav sections that require authorization greater than the base (least permission) role.
    'adminMinimumPermissions' => [
        ROUTE_LOGIN_ATTEMPTS => 'manager',
        ROUTE_SYSTEM_EVENTS => 'owner',
        ROUTE_ADMIN_ADMINS => 'director',
        ROUTE_ADMIN_ADMINS_INSERT => 'director',
        ROUTE_ADMIN_ADMINS_UPDATE => 'director',
        ROUTE_ADMIN_ADMINS_DELETE => 'owner',
        ROUTE_ADMIN_ROLES => 'director',
        ROUTE_ADMIN_ROLES_INSERT => 'director',
        ROUTE_ADMIN_ROLES_UPDATE => 'director',
        ROUTE_ADMIN_ROLES_DELETE => 'director',
        ROUTE_ADMIN_TESTIMONIALS_INSERT => 'manager',
        NAV_ADMIN_SYSTEM => 'director',
    ],

    'adminHomeRoutes' => [
        'usernames' => [],
        'roles' => [
            'owner' => ROUTE_SYSTEM_EVENTS,
            'director' => ROUTE_ADMIN_ADMINS,
            'manager' => ROUTE_ADMIN_TESTIMONIALS
        ]
    ],

    // when entering a new admin, role will default to this
    'adminDefaultRole' => 'user',

    'maxFailedLogins' => 5,

    'trimAllUserInput' => true,

    'navAdmin' => [
        'Marketing' => [
            // note, uncommenting the line below overrides the default setting
//            'minimumPermissions' => 'director',
            'subSections' => [
                'Testimonials' => [
                    'link' => ROUTE_ADMIN_TESTIMONIALS,
                    'subSections' => [
                        'Insert' => [
                            'link' => ROUTE_ADMIN_TESTIMONIALS_INSERT,
                        ]
                    ]
                ]
            ]
        ],

        'System' => [
            'subSections' => [
                'Events' => [
                    'link' => ROUTE_SYSTEM_EVENTS,
                ],

                'Admins' => [
                    'link' => ROUTE_ADMIN_ADMINS,
                    'subSections' => [

                        'Insert' => [
                            'link' => ROUTE_ADMIN_ADMINS_INSERT,
                        ],

                        'Roles' => [
                            'link' => ROUTE_ADMIN_ROLES,
                            'subSections' => [
                                'Insert' => [
                                    'link' => ROUTE_ADMIN_ROLES_INSERT,
                                ]
                            ],
                        ],

                        'Login Attempts' => [
                            'link' => ROUTE_LOGIN_ATTEMPTS,
                        ],
                    ]
                ]
            ]
        ]
    ]
];
