<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\Exceptions\EntityException;

class ArrayColumn extends AbstractColumn
{
    /**
     * @return array<array-key, mixed>
     * @throws EntityException
     */
    public function cast(mixed $dbValue): array
    {
        if (is_array($dbValue)) {
            return $dbValue;
        }
        try {
            return (array)\json_decode(strval($dbValue), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EntityException($e->getMessage(), $e);
        }
    }

    /**
     * @throws EntityException
     */
    public function uncast(mixed $entityValue): string
    {
        try {
            return \json_encode($entityValue, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EntityException($e->getMessage(), $e);
        }
    }
}