<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class UuidColumn extends AbstractColumn
{
    public function cast(mixed $dbValue): UuidInterface
    {
        return Uuid::fromString((string)$dbValue);
    }

    /**
     * @param UuidInterface $entityValue
     * @return string
     */
    public function uncast(mixed $entityValue): string
    {
        return $entityValue->toString();
    }
}