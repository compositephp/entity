<?php declare(strict_types=1);

namespace Composite\Entity;

use Composite\Entity\Exceptions\EntityException;

abstract class AbstractEntity implements \JsonSerializable
{
    /** @var Schema[] */
    private static array $_schemas = [];
    /** @var array<string, mixed>|null  */
    private ?array $_initialColumns = null;

    public static function schema(): Schema
    {
        $class = static::class;
        return self::$_schemas[$class] ?? (self::$_schemas[$class] = new Schema($class));
    }

    /**
     * @param array<string, mixed> $data
     * @throws EntityException
     */
    public static function fromArray(array $data = []): static
    {
        $schema = static::schema();
        if ($schema->hydrator) {
             $entity = $schema->hydrator->fromArray($data);
             $entity->_initialColumns = $schema->hydrator->toArray($entity);
             return $entity;
        }

        $class = $schema->class;
        $constructorData = $otherData = [];

        foreach ($schema->columns as $columnName => $column) {
            if (!array_key_exists($columnName, $data)) {
                continue;
            }
            $value = $data[$columnName];
            if ($value !== null || !$column->isNullable) {
                try {
                    $value = $column->cast($value);
                } catch (\Throwable $throwable) {
                    if ($column->hasDefaultValue) {
                        continue;
                    } elseif ($column->isNullable) {
                        $value = null;
                    } else {
                        throw EntityException::fromThrowable($throwable);
                    }
                }
            }
            if ($column->isConstructorPromoted) {
                $constructorData[$columnName] = $value;
            } else {
                $otherData[$columnName] = $value;
            }
        }
        /** @var AbstractEntity $entity */
        $entity = $constructorData ? new $class(...$constructorData) : new $class();

        $entity->_initialColumns = [];
        foreach ($schema->columns as $columnName => $column) {
            if (array_key_exists($columnName, $otherData)) {
                if ($column->isReadOnly) {
                    $column->setValue($entity, $otherData[$columnName]);
                } else {
                    $entity->{$columnName} = $otherData[$columnName];
                }
            }
            $columnValue = $entity->{$columnName};
            if ($columnValue === null && $column->isNullable) {
                $entity->_initialColumns[$columnName] = null;
            } else {
                $entity->_initialColumns[$columnName] = $column->uncast($columnValue);
            }
        }
        return $entity;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $schema = static::schema();
        if ($schema->hydrator) {
            return $schema->hydrator->toArray($this);
        }
        $result = [];
        foreach ($schema->columns as $columnName => $column) {
            if ($this->isNew() && !$column->isInitialized($this)) {
                continue;
            }
            $value = $this->{$columnName};
            if ($value === null && $column->isNullable) {
                $result[$columnName] = null;
            } else {
                $result[$columnName] = $column->uncast($value);
            }
        }
        return $result;
    }

    /**
     * @return array<string, mixed>
     * @throws EntityException
     */
    public function getChangedColumns(): array
    {
        $data = $this->toArray();
        if ($this->_initialColumns === null) {
            return $data;
        }
        $changedProperties = [];
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->_initialColumns) || $value !== $this->_initialColumns[$key]) {
                $changedProperties[$key] = $value;
            }
        }
        return $changedProperties;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    final public function isNew(): bool
    {
        return $this->_initialColumns === null;
    }

    final public function getOldValue(string $columnName): mixed
    {
        return $this->_initialColumns[$columnName] ?? null;
    }

    final public function resetChangedColumns(): void
    {
        $this->_initialColumns = $this->toArray();
    }
}
