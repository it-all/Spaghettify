<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Marketing\Testimonials;

use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\CrudController;
use function It_All\Spaghettify\Src\Infrastructure\Utilities\getRouteName;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class TestimonialsController extends CrudController
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

        try {
            $this->delete($response, $args,self::DELETE_RETURN_COLUMN);
        } catch (\Exception $e) {
            // no need to do anything, just redirect with error message already set
        }

        $redirectRoute = getRouteName(true, $this->routePrefix, 'index');
        return $response->withRedirect($this->router->pathFor($redirectRoute));
    }

}
