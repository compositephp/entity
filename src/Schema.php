<?php declare(strict_types=1);

namespace Composite\Entity;

use Composite\Entity\Columns\AbstractColumn;
use Composite\Entity\Exceptions\EntityException;

class Schema
{
    /**
     * @param $columns AbstractColumn[]
     */
    public function __construct(
        /** @psalm-var class-string $class */
        public readonly string $class,
        public readonly array $columns,
        public readonly array $attributes,
    ) {}

    /**
     * @psalm-param class-string $class
     * @throws EntityException
     */
    public static function build(string $class): self
    {
        try {
            $reflection = new \ReflectionClass($class);
        } catch (\ReflectionException $exception) {
            throw EntityException::fromThrowable($exception);
        }
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
     * @param array $data
     * @return array
     * @throws EntityException
     */
    public function castData(array $data): array
    {
        foreach ($this->columns as $column) {
            if (!isset($data[$column->name])) {
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
        foreach ($this->columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }
        return null;
    }

    /**
     * @return AbstractColumn[]
     */
    public function getConstructorColumns(): array
    {
        return array_filter($this->columns, fn(AbstractColumn $column) => $column->isConstructorPromoted);
    }

    /**
     * @return AbstractColumn[]
     */
    public function getNonConstructorColumns(): array
    {
        return array_filter($this->columns, fn(AbstractColumn $column) => !$column->isConstructorPromoted);
    }
}
