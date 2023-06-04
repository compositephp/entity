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
        return self::$_schemas[$class] ?? (self::$_schemas[$class] = Schema::build($class));
    }

    /**
     * @param array<string, mixed> $data
     * @throws EntityException
     */
    public static function fromArray(array $data = []): static
    {
        $schema = static::schema();
        $class = $schema->class;
        $preparedData = $schema->castData($data);
        $constructorData = [];

        foreach ($schema->getConstructorColumnNames() as $columnName) {
            if (!array_key_exists($columnName, $preparedData)) {
                continue;
            }
            $constructorData[$columnName] = $preparedData[$columnName];
        }

        $entity = $constructorData ? new $class(...$constructorData) : new $class();
        foreach ($schema->columns as $column) {
            if ($column->isConstructorPromoted || !array_key_exists($column->name, $preparedData)) {
                continue;
            }
            if ($column->isReadOnly) {
                $column->setValue($entity, $preparedData[$column->name]);
            } else {
                $entity->{$column->name} = $preparedData[$column->name];
            }
        }
        $entity->_initialColumns = $entity->toArray();
        return $entity;
    }

    /**
     * @return array<string, mixed>
     * @throws EntityException
     */
    public function toArray(): array
    {
        $result = [];
        foreach (static::schema()->columns as $column) {
            if ($this->isNew() && !$column->isInitialized($this)) {
                continue;
            }
            $value = $this->{$column->name};
            if ($value === null && $column->isNullable) {
                $result[$column->name] = null;
            } else {
                $result[$column->name] = $column->uncast($value);
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
        $changed_properties = [];
        foreach ($data as $key => $value) {
            if (!array_key_exists($key, $this->_initialColumns) || $value !== $this->_initialColumns[$key]) {
                $changed_properties[$key] = $value;
            }
        }
        return $changed_properties;
    }

    /**
     * @return array<string, mixed>
     * @throws EntityException
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
