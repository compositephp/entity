<?php declare(strict_types=1);

namespace Composite\Entity;

use Composite\Entity\Columns\AbstractColumn;
use Composite\Entity\Exceptions\EntityException;

class Schema
{
    private readonly array $constructorColumnNames;

    /**
     * @param class-string<AbstractEntity> $class
     * @param array<AbstractColumn> $columns
     * @param array<object> $attributes
     */
    public function __construct(
        public readonly string $class,
        public readonly array $columns,
        public readonly array $attributes,
    ) {
        $constructorColumnNames = [];
        foreach ($this->columns as $column) {
            if ($column->isConstructorPromoted) {
                $constructorColumnNames[] = $column->name;
            }
        }
        $this->constructorColumnNames = $constructorColumnNames;
    }

    /**
     * @param class-string<AbstractEntity> $class
     */
    public static function build(string $class): self
    {
        $reflection = new \ReflectionClass($class);
        $columns = ColumnBuilder::fromReflection($reflection);
        $attributes = array_map(
            fn (\ReflectionAttribute $attribute): object => $attribute->newInstance(),
            $reflection->getAttributes()
        );
        return new self(
            class: $class,
            columns: $columns,
            attributes: $attributes,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws EntityException
     */
    public function castData(array $data): array
    {
        foreach ($this->columns as $column) {
            if (!array_key_exists($column->name, $data)) {
                continue;
            }
            $value = $data[$column->name];
            if ($value === null && $column->isNullable) {
                continue;
            }
            try {
                $data[$column->name] = $column->cast($value);
            } catch (\Throwable $throwable) {
                if ($column->hasDefaultValue) {
                    unset($data[$column->name]);
                    continue;
                } elseif ($column->isNullable) {
                    $data[$column->name] = null;
                    continue;
                }
                throw EntityException::fromThrowable($throwable);
            }
        }
        return $data;
    }

    public function getColumn(string $name): ?AbstractColumn
    {
        return $this->columns[$name] ?? null;
    }

    public function getConstructorColumnNames(): array
    {
        return $this->constructorColumnNames;
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @return T|null
     */
    public function getFirstAttributeByClass(string $class): ?object
    {
        return current(array_filter($this->attributes, fn($attribute) => $attribute instanceof $class)) ?: null;
    }
}
