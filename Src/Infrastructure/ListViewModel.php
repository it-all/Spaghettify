<?php
declare(strict_types=1);

namespace It_All\Spaghettify\Src\Infrastructure;

use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseColumnModel;
use It_All\Spaghettify\Src\Infrastructure\Database\DatabaseTableModel;

class ListViewModel
{
    private $primaryTableModel;

    protected function __construct(DatabaseTableModel $primaryTableModel)
    {
        $this->primaryTableModel = $primaryTableModel;
    }

    public function getPrimaryTableModel(): DatabaseTableModel
    {
        return $this->primaryTableModel;
    }

    public function getListViewTitle(bool $plural = true): string
    {
        return $this->primaryTableModel->getFormalTableName($plural);
    }

    public function getUpdateColumnName(): string
    {
        return $this->primaryTableModel->getPrimaryKeyColumnName();
    }

    public function getOrderByColumnName(): string
    {
        return $this->primaryTableModel->getDefaultOrderByColumnName();
    }

    public function getIsOrderByAsc(): bool
    {
        return $this->primaryTableModel->getDefaultOrderByAsc();
    }

    public function getColumnByName(string $columnName): ?DatabaseColumnModel
    {
        return $this->primaryTableModel->getColumnByName($columnName);
    }
}
