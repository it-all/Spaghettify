<?php
declare(strict_types=1);

namespace Domain\Admin\Marketing\Testimonials;

use Infrastructure\Database\SingleTable\SingleTableController;
use function Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class TestimonialsController extends SingleTableController
{
    const DELETE_RETURN_COLUMN = 'person';

    public function __construct(Container $container)
    {
        parent::__construct($container, new TestimonialsModel(), new TestimonialsView($container), ROUTEPREFIX_ADMIN_TESTIMONIALS);
    }

    // override for custom return column
    public function getDelete(Request $request, Response $response, $args)
    {
        if (!$this->authorization->checkFunctionality(getRouteName(true, $this->routePrefix, 'delete'))) {
            throw new \Exception('No permission.');
        }

        return $this->getDeleteHelper($response, $args['primaryKey'],self::DELETE_RETURN_COLUMN, true);
    }

}
