<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Marketing\Testimonials;

use It_All\Spaghettify\Src\Infrastructure\Database\SingleTable\SingleTableView;
use Slim\Container;

class TestimonialsView extends SingleTableView
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new TestimonialsModel(), ROUTEPREFIX_ADMIN_TESTIMONIALS);
    }
}
