<?php
declare(strict_types=1);

namespace Domain\Admin;

use Infrastructure\AdminView;

class AdminHomeView extends AdminView
{
    public function index($request, $response, $args)
    {
        return $this->view->render(
            $response,
            'admin/home.twig',
            ['title' => 'Admin', 'navigationItems' => $this->navigationItems]
        );
    }
}
