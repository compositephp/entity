<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

class StringColumn extends AbstractColumn
{
    public function cast(mixed $dbValue): string
    {
        return strval($dbValue);
    }

    public function uncast(mixed $entityValue): string
    {
        return strval($entityValue);
    }
}