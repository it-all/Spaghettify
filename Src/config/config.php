<?php
declare(strict_types=1);

$domainName = 'it-all.com';

return [

    'businessName' => 'Spaghettify',

    'domainName' => $domainName,

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

        'pathCache' => APP_ROOT . '../storage/cache/'
    ],

    'pathTemplates' => APP_ROOT . 'templates/',
    'pathTwigMacros' => APP_ROOT . '../vendor/it-all/form-former/src/twigMacros',

    'errors' => [
        'emailTo' => ['owner', 'programmer'], // emails must be set in 'emails' section
        'fatalMessage' => 'Apologies, there has been an error on our site. We have been alerted and will correct it as soon as possible.',
        'echoDev' => true, // echo on dev servers (note, live server will never echo)
        'emailDev' => false // email on dev servers (note, live server will always email)
    ],

    'emails' => [
        'owner' => "owner@$domainName",
        'programmer' => "programmer@$domainName",
        'service' => "service@$domainName"
    ],

    // either functionCategory => minimumRole or functionCategory.function => minimum role
    // important to properly match the indexes to routes and nav authorization
    // the role values must be in the database: roles.role
    'adminMinimumPermissions' => [
        ROUTE_LOGIN_ATTEMPTS => 'manager',
        PERMISSION_ADMIN_SYSTEM => 'director',
        ROUTE_SYSTEM_EVENTS => 'owner',
        ROUTE_ADMIN_ADMINS => 'director',
        ROUTE_ADMIN_ADMINS_INSERT => 'director',
        ROUTE_ADMIN_ADMINS_UPDATE => 'director',
        ROUTE_ADMIN_ADMINS_DELETE => 'owner',
        ROUTE_ADMIN_ROLES => 'director',
        ROUTE_ADMIN_ROLES_INSERT => 'director',
        ROUTE_ADMIN_ROLES_UPDATE => 'director',
        ROUTE_ADMIN_ROLES_DELETE => 'director',
        PERMISSION_ADMIN_MARKETING => 'user',
        PERMISSION_ADMIN_TESTIMONIALS => 'user',
        ROUTE_ADMIN_TESTIMONIALS => 'user',
        ROUTE_ADMIN_TESTIMONIALS_INSERT => 'manager',
        ROUTE_ADMIN_TESTIMONIALS_UPDATE => 'user',
        ROUTE_ADMIN_TESTIMONIALS_DELETE => 'user',

    ],

    'adminHomeRoutes' => [
        'usernames' => [
            '123456' => ROUTE_ADMIN_ROLES
        ],
        'roles' => [
            'owner' => ROUTE_SYSTEM_EVENTS,
            'director' => ROUTE_ADMIN_ADMINS,
            'manager' => ROUTE_ADMIN_TESTIMONIALS
        ]
    ],

    // when entering a new admin, role will default to this
    'adminDefaultRole' => 'user',

    'maxFailedLogins' => 5,

    'trimAllUserInput' => true
];
