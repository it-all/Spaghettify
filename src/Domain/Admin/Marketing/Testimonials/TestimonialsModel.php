<?php
declare(strict_types=1);

namespace Domain\Admin\Marketing\Testimonials;

use Infrastructure\Database\SingleTable\SingleTableModel;

class TestimonialsModel extends SingleTableModel
{
    const TABLE_NAME = 'testimonials';

    public function __construct()
    {
        parent::__construct(self::TABLE_NAME, '*', 'receive_date', false);
    }
}
