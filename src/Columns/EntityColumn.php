<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Exceptions\EntityException;

class EntityColumn extends AbstractColumn
{
    /**
     * @throws EntityException
     */
    public function cast(mixed $dbValue): AbstractEntity
    {
        /** @var AbstractEntity $className */
        $className = $this->type;
        if ($dbValue instanceof $className) {
            return $dbValue;
        }
        if (!is_string($dbValue)) {
            throw new EntityException("Cannot to cast value for column {$this->name}, it must be string.");
        }
        try {
            $data = (array)\json_decode($dbValue, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EntityException($e->getMessage(), $e);
        }
        return $className::fromArray($data);
    }

    /**
     * @param AbstractEntity $entityValue
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