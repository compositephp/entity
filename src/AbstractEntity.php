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
                if ($column instanceof Columns\EntityColumn) {
                    $result[$columnName] = $value->jsonSerialize();
                } elseif ($column instanceof Columns\ArrayColumn) {
                    $result[$columnName] = $value;
                } elseif ($column instanceof Columns\EntityListColumn) {
                    $result[$columnName] = array_map(fn ($item) => $item->jsonSerialize(), $value);
                } else {
                    $result[$columnName] = $column->uncast($value);
                }
            }
        }
        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function __debugInfo(): array
    {
        $result = [];
        foreach ((new \ReflectionClass($this))->getProperties() as $property) {
            if (!$property->isInitialized($this)) {
                continue;
            }
            $propertyName = $property->name;
            if ($property->isPrivate()) {
                $propertyName .= ':private';
            }
            $propertyValue = $property->getValue($this);
            if ($propertyValue instanceof \Ramsey\Uuid\UuidInterface) {
                $propertyValue = $propertyValue->toString() . " (UUIDv{$propertyValue->getVersion()})";
            }
            $result[$propertyName] = $propertyValue;
        }
        return $result;
    }

    final public function isNew(): bool
    {
        return $this->_initialColumns === null;
    }

    final public function getOldValue(string $columnName): mixed
    {
        return $this->_initialColumns[$columnName] ?? null;
    }

    /**
     * @param array<string, mixed>|null $values An associative array with column names as keys and
     *                                          their respective values to reset, or null to reset all columns.
     */
    final public function resetChangedColumns(?array $values = null): void
    {
        if ($values) {
            $this->_initialColumns = $values + ($this->_initialColumns ?? []);
        } else {
            $this->_initialColumns = $this->toArray();
        }
    }
}
