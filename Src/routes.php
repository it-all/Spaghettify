<?php
declare(strict_types=1);

use It_All\Spaghettify\Src\Infrastructure\Security\Authentication\AuthenticationMiddleware;
use It_All\Spaghettify\Src\Infrastructure\Security\Authentication\GuestMiddleware;
use It_All\Spaghettify\Src\Infrastructure\Security\Authorization\AuthorizationMiddleware;

// For maximum performance, routes should not be grouped
// https://github.com/slimphp/Slim/issues/2165

// use as shortcuts for callables in routes

define('NAMESPACE_INFRASTRUCTURE', 'It_All\Spaghettify\Src\Infrastructure');
define('NAMESPACE_SECURITY', NAMESPACE_INFRASTRUCTURE. '\Security');
define('NAMESPACE_DOMAIN_FRONTEND', 'It_All\Spaghettify\Src\Domain\Frontend');
define('NAMESPACE_DOMAIN_ADMIN', 'It_All\Spaghettify\Src\Domain\Admin');

/////////////////////////////////////////
// Routes that anyone can access

$slim->get('/',
    NAMESPACE_DOMAIN_FRONTEND . '\HomeView:index')
    ->setName(ROUTE_HOME);

// remainder of front end pages to go here

// not found
$slim->get('/notFound',
    'It_All\Spaghettify\Src\Infrastructure\View:pageNotFound')
    ->setName(ROUTE_PAGE_NOT_FOUND);
/////////////////////////////////////////

/////////////////////////////////////////
// Routes that only non-authenticated users (Guests) can access

$slim->get('/' . $config['dirs']['admin'],
    NAMESPACE_SECURITY.'\Authentication\AuthenticationView:getLogin')
    ->add(new GuestMiddleware($container))
    ->setName(ROUTE_LOGIN);

$slim->post('/' . $config['dirs']['admin'],
    NAMESPACE_SECURITY.'\Authentication\AuthenticationController:postLogin')
    ->add(new GuestMiddleware($container))
    ->setName(ROUTE_LOGIN_POST);
/////////////////////////////////////////

// Admin Routes - Routes that only authenticated users access (to end of file)
// Note, if route needs authorization as well, the authorization is added prior to authentication, so that authentication is performed first

$slim->get('/' . $config['dirs']['admin'] . '/home',
    NAMESPACE_DOMAIN_ADMIN.'\AdminHomeView:index')
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_HOME_DEFAULT);

$slim->get('/' . $config['dirs']['admin'] . '/logout',
    NAMESPACE_SECURITY.'\Authentication\AuthenticationController:getLogout')
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_LOGOUT);

// system events
$slim->get('/' . $config['dirs']['admin'] . '/systemEvents',
    NAMESPACE_INFRASTRUCTURE.'\SystemEvents\SystemEventsView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_SYSTEM_EVENTS]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_SYSTEM_EVENTS);

// logins
$slim->get('/' . $config['dirs']['admin'] . '/logins',
    NAMESPACE_DOMAIN_ADMIN.'\Admins\Logins\LoginsView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_LOGIN_ATTEMPTS]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_LOGIN_ATTEMPTS);

// admins
$adminsPath = NAMESPACE_DOMAIN_ADMIN.'\Admins\\';
$slim->get('/' . $config['dirs']['admin'] . '/admins',
    $adminsPath . 'AdminsView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ADMINS]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ADMINS);

$slim->get('/' . $config['dirs']['admin'] . '/admins/insert',
    $adminsPath . 'AdminsView:getInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ADMINS_INSERT]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ADMINS_INSERT);

$slim->post('/' . $config['dirs']['admin'] . '/admins/insert',
    $adminsPath . 'AdminsController:postInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ADMINS_INSERT]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ADMINS_INSERT_POST);

$slim->get('/' . $config['dirs']['admin'] . '/admins/{primaryKey}',
    $adminsPath . 'AdminsView:getUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ADMINS_UPDATE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ADMINS_UPDATE);

$slim->put('/' . $config['dirs']['admin'] . '/admins/{primaryKey}',
    $adminsPath . 'AdminsController:putUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ADMINS_UPDATE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ADMINS_UPDATE_PUT);

$slim->get('/' . $config['dirs']['admin'] . '/admins/delete/{primaryKey}',
    $adminsPath . 'AdminsController:getDelete')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ADMINS_DELETE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ADMINS_DELETE);
// end admins

// roles
$rolesPath = NAMESPACE_DOMAIN_ADMIN.'\Admins\Roles\\';
$slim->get('/' . $config['dirs']['admin'] . '/roles',
    $rolesPath . 'RolesView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ROLES]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ROLES);

$slim->get('/' . $config['dirs']['admin'] . '/roles/insert',
    $rolesPath . 'RolesView:getInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ROLES_INSERT]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ROLES_INSERT);

$slim->post('/' . $config['dirs']['admin'] . '/roles/insert',
    $rolesPath . 'RolesController:postInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ROLES_INSERT]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ROLES_INSERT_POST);

$slim->get('/' . $config['dirs']['admin'] . '/roles/{primaryKey}',
    $rolesPath . 'RolesView:getUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ROLES_UPDATE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ROLES_UPDATE);

$slim->put('/' . $config['dirs']['admin'] . '/roles/{primaryKey}',
    $rolesPath . 'RolesController:putUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ROLES_UPDATE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ROLES_UPDATE_PUT);

$slim->get('/' . $config['dirs']['admin'] . '/roles/delete/{primaryKey}',
    $rolesPath . 'RolesController:getDelete')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_ROLES_DELETE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_ROLES_DELETE);
// end roles

// testimonials
$testimonialsPath = NAMESPACE_DOMAIN_ADMIN . '\Marketing\Testimonials\\';

$slim->get('/' . $config['dirs']['admin'] . '/testimonials',
    $testimonialsPath . 'TestimonialsView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_TESTIMONIALS]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_TESTIMONIALS);

$slim->get('/' . $config['dirs']['admin'] . '/testimonials/insert',
    $testimonialsPath . 'TestimonialsView:getInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_TESTIMONIALS_INSERT]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_TESTIMONIALS_INSERT);

$slim->post('/' . $config['dirs']['admin'] . '/testimonials/insert',
    $testimonialsPath . 'TestimonialsController:postInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_TESTIMONIALS_INSERT]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_TESTIMONIALS_INSERT_POST);

$slim->get('/' . $config['dirs']['admin'] . '/testimonials/{primaryKey}',
    $testimonialsPath . 'TestimonialsView:getUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_TESTIMONIALS_UPDATE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_TESTIMONIALS_UPDATE);

$slim->put('/' . $config['dirs']['admin'] . '/testimonials/{primaryKey}',
    $testimonialsPath . 'TestimonialsController:putUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_TESTIMONIALS_UPDATE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_TESTIMONIALS_UPDATE_PUT);

$slim->get('/' . $config['dirs']['admin'] . '/testimonials/delete/{primaryKey}',
    $testimonialsPath . 'TestimonialsController:getDelete')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions'][ROUTE_ADMIN_TESTIMONIALS_DELETE]))
    ->add(new AuthenticationMiddleware($container))
    ->setName(ROUTE_ADMIN_TESTIMONIALS_DELETE);
// end testimonials

