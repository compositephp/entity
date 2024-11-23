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
                $dbValue = (array)\json_decode(
                    json: $dbValue,
                    associative: true,
                    flags: JSON_THROW_ON_ERROR,
                );
            } catch (\JsonException $e) {
                throw new EntityException($e->getMessage(), $e);
            }
        } elseif (!is_array($dbValue)) {
            throw new EntityException("Cannot to cast value for column {$this->name}, it must be string or array.");
        }
        $result = [];
        foreach ($dbValue as $data) {
            if (!$entity = $this->getEntity($data)) {
                continue;
            }
            if ($this->subType && isset($entity->{$this->subType})) {
                $key = $entity->{$this->subType};
                if ($key instanceof \BackedEnum) {
                    $key = $key->value;
                } elseif ($key instanceof \UnitEnum) {
                    $key = $key->name;
                }
                $result[$key] = $entity;
            } else {
                $result[] = $entity;
            }
        }
        return $result;
    }

    /**
     * @param array<AbstractEntity> $entityValue
     * @throws EntityException
     */
    public function uncast(mixed $entityValue): string
    {
        $list = [];
        foreach ($entityValue as $item) {
            if ($item instanceof $this->type) {
                $data = $item->toArray();
                if ($this->subType && isset($data[$this->subType])) {
                    $key = $item->{$this->subType};
                    if ($key instanceof \BackedEnum) {
                        $key = $key->value;
                    } elseif ($key instanceof \UnitEnum) {
                        $key = $key->name;
                    }
                    $list[$key] = $data;
                } else {
                    $list[] = $data;
                }
            }
        }
        try {
            return json_encode($list, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EntityException($e->getMessage(), $e);
        }
    }

    private function getEntity(mixed $data): ?AbstractEntity
    {
        if ($data === null) {
            return null;
        }
        /** @var class-string<AbstractEntity> $entityClass */
        $entityClass = $this->type;
        if (is_object($data) && $data::class === $entityClass) {
            return $data;
        }
        if (!\is_array($data)) {
            return null;
        }
        return $entityClass::fromArray($data);
    }
}