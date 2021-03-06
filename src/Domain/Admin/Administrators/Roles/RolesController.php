<?php
declare(strict_types=1);

namespace Domain\Admin\Administrators\Roles;

use Infrastructure\Database\SingleTable\SingleTableController;
use Slim\Container;

class RolesController extends SingleTableController
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new RolesModel($container->settings['adminDefaultRole']), new RolesView($container), ROUTEPREFIX_ADMIN_ROLES);
    }

    // override to check condition and add custom return column
    protected function delete($primaryKey, string $returnColumn = null, bool $sendEmail = false)
    {
        // make sure role is not being used
        if ($this->model::hasAdmin((int) $primaryKey)) {
            $_SESSION[SESSION_ADMIN_NOTICE] = ["Role in use", 'adminNoticeFailure'];
            return false;
        }

        parent::delete($primaryKey, 'role', $sendEmail);
    }
}
