<?php declare(strict_types=1);

namespace Composite\Entity;

use Composite\Entity\Attributes\Hydrator;
use Composite\Entity\Columns\AbstractColumn;

class Schema
{
    /** @var array<string, AbstractColumn> $columns */
    public readonly array $columns;
    /** @var array<object> */
    public readonly array $attributes;
    public readonly ?HydratorInterface $hydrator;

    public function __construct(
        public readonly string $class,
    ) {
        $reflection = new \ReflectionClass($class);
        $attributes = [];
        $hydrator = null;
        foreach ($reflection->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();
            $attributes[] = $attributeInstance;
            if ($attributeInstance instanceof Hydrator) {
                $hydrator = $attributeInstance->hydrator;
            }
        }
        $this->attributes = $attributes;
        $this->hydrator = $hydrator;
        $this->columns = ColumnBuilder::fromReflection($reflection);
    }

    public function getColumn(string $name): ?AbstractColumn
    {
        return $this->columns[$name] ?? null;
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
