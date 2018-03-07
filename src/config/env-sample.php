<?php

$domainName = 'example.com';

return [

    'businessName' => 'Our Biz, LLC',

    /* Doing Business As (Informal Business Name), leave blank to use businessName above */
    'businessDba' => 'Our Biz',

    'domainName' => $domainName, // not currently referenced in the app

    /* set true for live server */
    'isLive' => false,

    'domainUseWww' => false,

    'dirs' => [
        'admin' => 'private' // browse to this dir to get to the admin login page
    ],

    'database' => [
        'name' => 'mydbname',
        'username' => 'mydbname',
        'password' => 'mypassword',
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

    'errors' => [
        'emailDev' => true
    ],

    /* uncomment below to use the default session save path from php.ini instead of storage/sessions */
//    'session' => [
//        'savePath' => null
//    ],

    'storage' => [
        'cache' => [
            /* for increased performance, remove this entry when routes are stable (recommended for production use only, and mainly improves speed for many routes that have parameters: https://akrabat.com/slims-route-cache-file/ : "Note that there's no invalidation on this cache, so if you add or change any routes, you need to delete this file. Generally, it's best to only set this in production.") */
            'routerCacheFile' => false
        ]
    ],

    /* true is good when developing in order to recompile twig templates when source code changes (https://twig.symfony.com/doc/2.x/api.html) */
    'twigAutoReload' => true
];
