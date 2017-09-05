<?php
declare(strict_types=1);

use It_All\Spaghettify\Src\Infrastructure\Security\Authentication\AuthenticationMiddleware;
use It_All\Spaghettify\Src\Infrastructure\Security\Authentication\GuestMiddleware;
use It_All\Spaghettify\Src\Infrastructure\Security\Authorization\AuthorizationMiddleware;

// For maximum performance, routes should not be grouped
// https://github.com/slimphp/Slim/issues/2165

// use as shortcuts for callables in routes
$securityNs = 'It_All\Spaghettify\Src\Infrastructure\Security';
$domainFrontendNs = 'It_All\Spaghettify\Src\Domain\Frontend';
$domainAdminNs = 'It_All\Spaghettify\Src\Domain\Admin';

/////////////////////////////////////////
// Routes that anyone can access

$slim->get('/',
    $domainFrontendNs . '\HomeView:index')
    ->setName('home');

// remainder of front end pages to go here

// not found
$slim->get('/notFound',
    'It_All\Spaghettify\Src\Infrastructure\View:pageNotFound')
    ->setName('pageNotFound');
/////////////////////////////////////////

/////////////////////////////////////////
// Routes that only non-authenticated users (Guests) can access

$slim->get('/' . $config['dirs']['admin'],
    $securityNs.'\Authentication\AuthenticationView:getLogin')
    ->add(new GuestMiddleware($container))
    ->setName('authentication.login');

$slim->post('/' . $config['dirs']['admin'],
    $securityNs.'\Authentication\AuthenticationController:postLogin')
    ->add(new GuestMiddleware($container))
    ->setName('authentication.post.login');
/////////////////////////////////////////

// Routes that only authenticated users access (to end of file)
// Note, if route needs authorization as well, the authorization is added prior to authentication, so that authentication is performed first

$slim->get('/' . $config['dirs']['admin'] . '/home',
    $domainAdminNs.'\AdminHomeView:index')
    ->add(new AuthenticationMiddleware($container))
    ->setName('admin.home');

$slim->get('/' . $config['dirs']['admin'] . '/logout',
    $securityNs.'\Authentication\AuthenticationController:getLogout')
    ->add(new AuthenticationMiddleware($container))
    ->setName('authentication.logout');

// logins
$loginsPath = $domainAdminNs.'\Admins\Logins\\';
$slim->get('/' . $config['dirs']['admin'] . '/logins',
    $loginsPath . 'LoginsView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['logins.index']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('logins.index');

// admins
$adminsPath = $domainAdminNs.'\Admins\\';
$slim->get('/' . $config['dirs']['admin'] . '/admins',
    $adminsPath . 'AdminsView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['admins.index']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('admins.index');

$slim->get('/' . $config['dirs']['admin'] . '/admins/insert',
    $adminsPath . 'AdminsView:getInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['admins.insert']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('admins.insert');

$slim->post('/' . $config['dirs']['admin'] . '/admins/insert',
    $adminsPath . 'AdminsController:postInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['admins.insert']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('admins.post.insert');

$slim->get('/' . $config['dirs']['admin'] . '/admins/{primaryKey}',
    $adminsPath . 'AdminsView:getUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['admins.update']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('admins.update');

$slim->put('/' . $config['dirs']['admin'] . '/admins/{primaryKey}',
    $adminsPath . 'AdminsController:putUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['admins.update']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('admins.put.update');

$slim->get('/' . $config['dirs']['admin'] . '/admins/delete/{primaryKey}',
    $adminsPath . 'AdminsController:getDelete')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['admins.delete']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('admins.delete');
// end admins

// roles
$rolesPath = $domainAdminNs.'\Admins\Roles\\';
$slim->get('/' . $config['dirs']['admin'] . '/roles',
    $rolesPath . 'RolesView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['roles.index']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('roles.index');

$slim->get('/' . $config['dirs']['admin'] . '/roles/insert',
    $rolesPath . 'RolesView:getInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['roles.insert']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('roles.insert');

$slim->post('/' . $config['dirs']['admin'] . '/roles/insert',
    $rolesPath . 'RolesController:postInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['roles.insert']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('roles.post.insert');

$slim->get('/' . $config['dirs']['admin'] . '/roles/{primaryKey}',
    $rolesPath . 'RolesView:getUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['roles.update']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('roles.update');

$slim->put('/' . $config['dirs']['admin'] . '/roles/{primaryKey}',
    $rolesPath . 'RolesController:putUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['roles.update']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('roles.put.update');

$slim->get('/' . $config['dirs']['admin'] . '/roles/delete/{primaryKey}',
    $rolesPath . 'RolesController:getDelete')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['roles.delete']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('roles.delete');
// end roles

// testimonials
$testimonialsPath = $domainAdminNs . '\Marketing\Testimonials\\';

$slim->get('/' . $config['dirs']['admin'] . '/testimonials',
    $testimonialsPath . 'TestimonialsView:index')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['testimonials.index']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('testimonials.index');

$slim->get('/' . $config['dirs']['admin'] . '/testimonials/insert',
    $testimonialsPath . 'TestimonialsView:getInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['testimonials.insert']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('testimonials.insert');

$slim->post('/' . $config['dirs']['admin'] . '/testimonials/insert',
    $testimonialsPath . 'TestimonialsController:postInsert')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['testimonials.insert']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('testimonials.post.insert');

$slim->get('/' . $config['dirs']['admin'] . '/testimonials/{primaryKey}',
    $testimonialsPath . 'TestimonialsView:getUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['testimonials.update']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('testimonials.update');

$slim->put('/' . $config['dirs']['admin'] . '/testimonials/{primaryKey}',
    $testimonialsPath . 'TestimonialsController:putUpdate')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['testimonials.update']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('testimonials.put.update');

$slim->get('/' . $config['dirs']['admin'] . '/testimonials/delete/{primaryKey}',
    $testimonialsPath . 'TestimonialsController:getDelete')
    ->add(new AuthorizationMiddleware($container, $config['adminMinimumPermissions']['testimonials.delete']))
    ->add(new AuthenticationMiddleware($container))
    ->setName('testimonials.delete');
// end testimonials

