<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Exceptions\EntityException;

class EntityListColumn extends AbstractColumn
{
    private readonly ?string $keyColumn;
    /**
     * @param array<string, object> $attributes
     */
    public function __construct(
        string $name,
        string $type,
        ?string $keyColumn,
        array $attributes,
        bool $hasDefaultValue,
        mixed $defaultValue,
        bool $isNullable,
        bool $isReadOnly,
        bool $isConstructorPromoted,
    ) {
        $this->keyColumn = $keyColumn;
        parent::__construct(
            name: $name,
            type: $type,
            attributes: $attributes,
            hasDefaultValue: $hasDefaultValue,
            defaultValue: $defaultValue,
            isNullable: $isNullable,
            isReadOnly: $isReadOnly,
            isConstructorPromoted: $isConstructorPromoted,
        );
    }

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
        foreach ($dbValue as $data) {
            if (!$entity = $this->getEntity($data)) {
                continue;
            }
            if ($this->keyColumn && isset($entity->{$this->keyColumn})) {
                $result[$entity->{$this->keyColumn}] = $entity;
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
                if ($this->keyColumn && isset($data[$this->keyColumn])) {
                    $list[$data[$this->keyColumn]] = $data;
                } else {
                    $list[] = $data;
                }
            }
        }
        try {
            return \json_encode($list, JSON_THROW_ON_ERROR);
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
        if ($data instanceof $entityClass) {
            return $data;
        }
        if (!is_array($data)) {
            if (!is_string($data) || !$data) {
                return null;
            }
            try {
                $data = (array)\json_decode($data, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }
        }
        return $entityClass::fromArray($data);
    }
}