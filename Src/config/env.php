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
        'name' => 'spaghettify',
        'username' => 'spaghettify',
        'password' => 'woolsocks',
        'host' => '127.0.0.1',
        'port' => 5432
    ],

    'phpmailer' => [
        'protocol' => 'smtp',
        'smtpHost' => 'relay.pair.com',
        'smtpPort' => 2525
    ],

    'emails' => [
        'owner' => "owner@$domainName",
        'programmer' => "programmer@$domainName",
        'service' => "service@$domainName"
    ],

    'storage' => [
        'cache' => [
            'routerCacheFile' => false // for increased performance, remove this entry when routes are stable (recommended for production use only, and mainly improves speed for many routes that have parameters: https://akrabat.com/slims-route-cache-file/ : "Note that there's no invalidation on this cache, so if you add or change any routes, you need to delete this file. Generally, it's best to only set this in production.")
        ]
    ],

    'twigAutoReload' => true
];
