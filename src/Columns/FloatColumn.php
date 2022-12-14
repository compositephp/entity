<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

class FloatColumn extends AbstractColumn
{
    public function cast(mixed $dbValue): float
    {
        return floatval($dbValue);
    }

    public function uncast(mixed $entityValue): float
    {
        return floatval($entityValue);
    }
}