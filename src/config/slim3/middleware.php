<?php
declare(strict_types=1);

// handle CSRF check failures and allow Twig to access and insert CSRF fields to forms
$slim->add(new \Infrastructure\Security\CsrfMiddleware($container));
// slim CSRF check middleware
$slim->add($container->csrf);
