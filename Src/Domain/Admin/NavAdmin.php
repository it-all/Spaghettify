<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin;
use It_All\Spaghettify\Src\Infrastructure\Security\Authorization\AuthorizationService;
use Slim\Container;

/**
 * navigation for admin pages
 */
class NavAdmin
{
    private $nav;

    function __construct(Container $container)
    {
        $this->setNav($container);
        return $this;
    }

    private function setNav(Container $container)
    {
        $this->nav = [

            'Marketing' => [
                'minimumPermissions' => $container->authorization->getMinimumPermission('marketing'),
                'subSections' => [
                    'Testimonials' => [
                        'minimumPermissions' => $container->authorization->getMinimumPermission('testimonials.index'),
                        'link' => 'testimonials.index',
                        'subSections' => [
                            'Insert' => [
                                'minimumPermissions' => $container->authorization->getMinimumPermission('testimonials.insert'),
                                'link' => 'testimonials.insert',
                            ]
                        ]
                    ]
                ]
            ],

            'Admins' => [
                'minimumPermissions' => $container->authorization->getMinimumPermission('admins.index'),
                'link' => 'admins.index',
                'subSections' => [

                    'Insert' => [
                        'minimumPermissions' => $container->authorization->getMinimumPermission('admins.insert'),
                        'link' => 'admins.insert',
                    ],

                    'Roles' => [
                        'minimumPermissions' => $container->authorization->getMinimumPermission('roles.index'),
                        'link' => 'roles.index',
                        'subSections' => [
                            'Insert' => [
                            'minimumPermissions' => $container->authorization->getMinimumPermission('roles.insert'),
                            'link' => 'roles.insert',
                            ]
                        ],
                    ],

                    'Login Attempts' => [
                        'minimumPermissions' => $container->authorization->getMinimumPermission('logins.index'),
                        'link' => 'logins.index',
                    ],

                ]
            ]
        ];
    }

    private function getSectionForUserRecurs(AuthorizationService $autho, array $section, string $sectionName)
    {
        // if there are section permissions and they are not met
        if (isset($section['minimumPermissions']) && !$autho->check($section['minimumPermissions'])) {
            return false;
        }

        // rebuild based on permissions
        $updatedSection = [];
        foreach ($section as $key => $value) {
            if ($key != 'subSections') {
                $updatedSection[$key] = $value;
            }
        }

        $updatedSubSections = [];
        if (isset($section['subSections'])) {
            foreach ($section['subSections'] as $subSectionName => $subSection) {

                $updatedSubSection = $this->getSectionForUserRecurs($autho, $subSection, $subSectionName);
                // CAREFUL, empty arrays evaluate to false
                if ($updatedSubSection !== false) {
                    $updatedSubSections[$subSectionName] = $updatedSubSection;
                }
            }
        }

        if (count($updatedSubSections) > 0) {
            $updatedSection['subSections'] = $updatedSubSections;
        }

        return $updatedSection;

    }

    public function getNavForUser(AuthorizationService $autho)
    {
        $nav = []; // rebuild nav sections based on authorization for this user

        foreach ($this->nav as $sectionName => $section) {
            $updatedSection = $this->getSectionForUserRecurs($autho, $section, $sectionName);
            // CAREFUL, empty arrays evaluate to false
            if ($updatedSection !== false) {
                $nav[$sectionName] = $updatedSection;
            }
        }

        return $nav;
    }
}
