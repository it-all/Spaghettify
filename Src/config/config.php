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
    // important to properly match the indexes to routes and nav authorization
    // the role values must be in the database: roles.role
    // required for every route and unlinked nav section
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
        ROUTE_ADMIN_TESTIMONIALS => 'user',
        ROUTE_ADMIN_TESTIMONIALS_INSERT => 'manager',
        ROUTE_ADMIN_TESTIMONIALS_UPDATE => 'user',
        ROUTE_ADMIN_TESTIMONIALS_DELETE => 'user',
        NAV_ADMIN_SYSTEM => 'director',
        NAV_ADMIN_MARKETING => 'user',
        NAV_ADMIN_TESTIMONIALS => 'user'
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
