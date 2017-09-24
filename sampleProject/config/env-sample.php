<?php

$domainName = 'example.com';

return [

    'businessName' => 'Our Biz, LLC',

    'domainName' => $domainName,

    /** set true for live server */
    'isLive' => false,

    /** remove for live server or change to your dev server name */
    'hostName' => 'localhost',

    'dirs' => [
        'admin' => 'private'
    ],

    'database' => [
        'name' => 'yourDbName',
        'username' => 'yourDbUsername',
        'password' => 'yourDbPw',
        'host' => '127.0.0.1',
        'port' => 5432
    ],

    'phpmailer' => [
        'protocol' => 'smtp',
        'smtpHost' => 'yourSmtpHost',
        'smtpPort' => 2525
    ],

    'emails' => [
        'owner' => "owner@$domainName",
        'programmer' => "programmer@$domainName",
        'service' => "service@$domainName"
    ],

    'storage' => [
        'cache' => [
            'routerCacheFile' => false // remove this entry when routes are stable for increased performance
        ]
    ],

    'twigAutoReload' => true,

    // to set/change default admin home pages (be sure permissions are ok).
    /*
    'adminHomeRoutes' => [
        'usernames' => [
            // username takes precedence over role
            'owner' => ROUTE_ADMIN_ROLES
        ],
        'roles' => [
            'owner' => ROUTE_SYSTEM_EVENTS,
            'director' => ROUTE_ADMIN_ADMINS,
            'manager' => ROUTE_ADMIN_TESTIMONIALS
        ]
    ]
    */

    // to add new admin nav sections and subsections,
    /*

    // new section:
    // first add the nav section constant in index.php ie:
    // define('NAV_ADMIN_TEST', ROUTEPREFIX_ADMIN.'.'.'test');

    // then define the minimum permissions necessary to see the nav section
    'adminMinimumPermissions' => [
        NAV_ADMIN_TEST => 'user'
    ],

    // then add the new section or subsection to the nav

    // new section like this:
    'navAdmin' => [
        'Test' => [.. see config.php for what goes here]
    ]

    // new subsection (of System) like this:
    'navAdmin' => [
        'System' => [
            'subSections' => [
                'test' => [.. see config.php for what goes here]
            ]
        ]
    ]

    // note, to have spaces in your section or subsection name, ie 'test x', replace the spaces with underscores in the initial constant defined in index.php, ie define('NAV_ADMIN_TEST_X', ROUTEPREFIX_ADMIN.'.'.'test_x');
    */


    // to add a slim dependency into the container, define $yourObject at the top of this file
    /*
    'slimDependencies' => [
        'test' => function($container) use ($yourObject) {
            return $yourObject;
        }
    ]
    */
];
