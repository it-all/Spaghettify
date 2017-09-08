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
        'logins.index' => 'manager',
        'admins.index' => 'manager',
        'admins.insert' => 'director',
        'admins.update' => 'director',
        'admins.delete' => 'owner',
        'roles.index' => 'manager',
        'roles.insert' => 'director',
        'roles.update' => 'director',
        'roles.delete' => 'director',
        'marketing' => 'user',
        'testimonials' => 'user',
        'testimonials.index' => 'user',
        'testimonials.insert' => 'manager',
        'testimonials.update' => 'user',
        'testimonials.delete' => 'user',

    ],

    'adminHomeRoute' => [
        'owner' => 'logins.index',
        'director' => 'admins.index',
        'manager' => 'testimonials.index'
    ],

    // when entering a new admin, role will default to this
    'adminDefaultRole' => 'user',

    'maxFailedLogins' => 500,

    'trimAllUserInput' => true
];
