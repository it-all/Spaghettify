<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin;
use Slim\Container;

/**
 * navigation for admin pages
 */
class NavAdmin
{
    private $nav;
    private $container;

    function __construct(Container $container)
    {
        $this->setNav();
        $this->container = $container;
    }

    private function setNav()
    {
        $this->nav = [

            'Marketing' => [
                // note, uncommenting the line below overrides the default setting
//            'minimumPermissions' => 'director',
                'subSections' => [
                    'Testimonials' => [
                        'link' => ROUTE_ADMIN_TESTIMONIALS,
                        'subSections' => [
                            'Insert' => [
                                'link' => ROUTE_ADMIN_TESTIMONIALS_INSERT,
                            ]
                        ]
                    ]
                ]
            ],

            'System' => [
                'subSections' => [
                    'Events' => [
                        'link' => ROUTE_SYSTEM_EVENTS_RESET,
                    ],

                    'Admins' => [
                        'link' => ROUTE_ADMIN_ADMINS_RESET,
                        'subSections' => [

                            'Insert' => [
                                'link' => ROUTE_ADMIN_ADMINS_INSERT,
                            ],

                            'Roles' => [
                                'link' => ROUTE_ADMIN_ROLES,
                                'subSections' => [
                                    'Insert' => [
                                        'link' => ROUTE_ADMIN_ROLES_INSERT,
                                    ]
                                ],
                            ],

                            'Login Attempts' => [
                                'link' => ROUTE_LOGIN_ATTEMPTS,
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }

    // precedence:
    // 1. directly set by minimumPermissions key in the section
    // 2. by section link
    // 3. by section name
    private function getSectionMinimumPermission(array $section, string $sectionName)
    {
        if (isset($section['minimumPermissions'])) {
            return $section['minimumPermissions'];
        }

        if (isset($section['link'])) {
            return $this->container->authorization->getMinimumPermission($section['link']);
        }

        // by nav section - ie NAV_ADMIN_SYSTEM
        return $this->container->authorization->getMinimumPermission(constant('NAV_ADMIN_'.strtoupper(str_replace(" ", "_", $sectionName))));

    }

    private function getSectionForUserRecurs(array $section, string $sectionName)
    {
        // if there are section permissions and they are not met
        if ($minimumPermissions = $this->getSectionMinimumPermission($section, $sectionName)) {
            if (!$this->container->authorization->check($minimumPermissions)) {
                return false;
            }
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

                $updatedSubSection = $this->getSectionForUserRecurs($subSection, $subSectionName);
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

    public function getNavForUser()
    {
        $nav = []; // rebuild nav sections based on authorization for this user

        foreach ($this->nav as $sectionName => $section) {
            $updatedSection = $this->getSectionForUserRecurs($section, $sectionName);
            // CAREFUL, empty arrays evaluate to false
            if ($updatedSection !== false) {
                $nav[$sectionName] = $updatedSection;
            }
        }

        return $nav;
    }
}
