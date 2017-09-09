<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Frontend;

use It_All\Spaghettify\Src\Infrastructure\View;

class HomeView extends View
{
    public function index($request, $response)
    {
        $adminLinkRoute = $this->authentication->getAdminHomeRouteForUser();

        return $this->view->render(
            $response,
            'frontend/home.twig',
            ['title' => $this->settings['businessName'], 'pageType' => 'public', 'adminLinkRoute' => $adminLinkRoute]
        );
    }
}
