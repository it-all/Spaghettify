<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Domain\Admin\Marketing\Testimonials;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;

class TestimonialsModel extends DatabaseTableModel
{
    const TABLE_NAME = 'testimonials';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, 'receive_date', false);
    }
}
