<?php

return [

    /** set true for live server */
    'isLive' => false,

    /** remove for live server */
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
        'owner' => 'owner@example.com',
        'programmer' => 'programmer@example.com',
        'service' => 'service@example.com'
    ],

    'twigAutoReload' => true
];
