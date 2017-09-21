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

    'twigAutoReload' => true
];
