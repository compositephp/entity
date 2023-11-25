<?php declare(strict_types=1);

namespace Composite\Entity;

use Composite\Entity\Attributes\Hydrator;
use Composite\Entity\Columns\AbstractColumn;

class Schema
{
    /** @var class-string<AbstractEntity> $class */
    public readonly string $class;
    /** @var array<string, AbstractColumn> $columns */
    public readonly array $columns;
    /** @var array<object> */
    public readonly array $attributes;
    public readonly ?HydratorInterface $hydrator;

    /**
     * @param class-string<AbstractEntity> $class
     */
    public function __construct(string $class)
    {
        $this->class = $class;
        $reflection = new \ReflectionClass($this->class);
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
        foreach ($this->attributes as $attribute) {
            if ($attribute instanceof $class) {
                return $attribute;
            }
        }
        return null;
    }
}
