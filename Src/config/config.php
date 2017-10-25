<?php
declare(strict_types=1);

$domainName = 'it-all.com';

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
            'pathPhpErrors' => APP_ROOT . '../storage/logs/phpErrors.log'
        ],

        'cache' => [
            'pathCache' => APP_ROOT . '../storage/cache/',
            'routerCacheFile' => APP_ROOT . '../storage/cache/router.txt'
        ]
    ],

    'pathTemplates' => APP_ROOT . 'templates/',
    'pathTwigMacros' => APP_ROOT . '../vendor/it-all/form-former/src/twigMacros',

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
        ROUTE_LOGIN_ATTEMPTS => 'owner',
        ROUTE_LOGIN_ATTEMPTS_RESET => 'owner',
        ROUTE_SYSTEM_EVENTS => 'owner',
        ROUTE_SYSTEM_EVENTS_RESET => 'owner',
        ROUTE_ADMIN_ADMINS => 'owner',
        ROUTE_ADMIN_ADMINS_RESET => 'owner',
        ROUTE_ADMIN_ADMINS_INSERT => 'owner',
        ROUTE_ADMIN_ADMINS_UPDATE => 'owner',
        ROUTE_ADMIN_ADMINS_DELETE => 'owner',
        ROUTE_ADMIN_ROLES => 'owner',
        ROUTE_ADMIN_ROLES_INSERT => 'owner',
        ROUTE_ADMIN_ROLES_UPDATE => 'owner',
        ROUTE_ADMIN_ROLES_DELETE => 'owner',
        ROUTE_ADMIN_TESTIMONIALS_INSERT => 'owner',
        NAV_ADMIN_SYSTEM => 'owner',
    ],

    'adminHomeRoutes' => [
        'usernames' => [],
        'roles' => [
            'owner' => ROUTE_SYSTEM_EVENTS
        ]
    ],

    // when entering a new admin, role will default to this
    'adminDefaultRole' => 'owner',

    'maxFailedLogins' => 5,

    'trimAllUserInput' => true
];
