<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure;

class Middleware
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }
}
