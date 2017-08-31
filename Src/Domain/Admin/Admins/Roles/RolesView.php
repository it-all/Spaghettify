<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Admins\Roles;

use It_All\FormFormer\Fields\InputField;
use It_All\FormFormer\Form;
use It_All\Spaghettify\Src\Infrastructure\Database\CRUD\AdminCrudView;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\DatabaseTableForm;
use It_All\Spaghettify\Src\Infrastructure\UserInterface\Forms\FormHelper;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Container;

class RolesView extends AdminCrudView
{
    public function __construct(Container $container)
    {
        parent::__construct($container, new RolesModel(), 'roles');
    }
}
