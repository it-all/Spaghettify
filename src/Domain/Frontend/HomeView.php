<?php
declare(strict_types=1);

namespace Domain\Frontend;

use Infrastructure\View;

class HomeView extends View
{
    public function index($request, $response)
    {
        return $this->view->render(
            $response,
            'frontend/home.twig',
            ['title' => $this->settings['businessName'], 'pageType' => 'public', 'adminLinkRoute' => $this->authentication->getAdminHomeRouteForUser()]
        );
    }
}
