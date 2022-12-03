<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Exceptions\EntityException;

class EntityListColumn extends AbstractColumn
{
    /**
     * @return array<AbstractEntity>
     * @throws EntityException
     */
    public function cast(mixed $dbValue): array
    {
        if (is_string($dbValue)) {
            try {
                $dbValue = (array)\json_decode($dbValue, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new EntityException($e->getMessage(), $e);
            }
        } elseif (!is_array($dbValue)) {
            throw new EntityException("Cannot to cast value for column {$this->name}, it must be string or array.");
        }
        $result = [];
        /** @var class-string<AbstractEntity> $entityClass */
        $entityClass = $this->type;
        foreach ($dbValue as $data) {
            if ($data instanceof $entityClass) {
                $result[] = $data;
                continue;
            }
            if (!is_array($data)) {
                if (!is_string($data) || !$data) {
                    continue;
                }
                try {
                    $data = (array)\json_decode($data, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException) {
                    continue;
                }
            }
            $result[] = $entityClass::fromArray($data);
        }
        return $result;
    }

    /**
     * @param array<AbstractEntity> $entityValue
     * @throws EntityException
     */
    public function uncast(mixed $entityValue): string
    {
        $data = [];
        foreach ($entityValue as $item) {
            if ($item instanceof $this->type) {
                $data[] = $item->toArray();
            }
        }
        try {
            return \json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EntityException($e->getMessage(), $e);
        }
    }
}