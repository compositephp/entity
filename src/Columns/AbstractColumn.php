<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\AbstractEntity;

abstract class AbstractColumn
{
    private ?\ReflectionProperty $reflectionProperty = null;

    /**
     * @param array<string, object> $attributes
     */
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly array $attributes,
        public readonly bool $hasDefaultValue,
        public readonly mixed $defaultValue,
        public readonly bool $isNullable,
        public readonly bool $isReadOnly,
        public readonly bool $isConstructorPromoted,
    ) {}

    /**
     * @param mixed $dbValue value from your database
     * @return mixed value for your Entity, null if impossible to cast
     */
    abstract public function cast(mixed $dbValue): mixed;

    /**
     * @param mixed $entityValue value from your Entity
     * @return string|int|float|bool|null value for your database, null if impossible to uncast
     */
    abstract public function uncast(mixed $entityValue): string|int|float|bool|null;

    public function isInitialized(AbstractEntity $entity): bool
    {
        if ($this->isConstructorPromoted || $this->hasDefaultValue) {
            return true;
        }
        return $this->getReflectionProperty($entity)->isInitialized($entity);
    }

    public function setValue(AbstractEntity $entity, mixed $value): void
    {
        $this->getReflectionProperty($entity)->setValue($entity, $value);
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

    private function getReflectionProperty(AbstractEntity $entity): \ReflectionProperty
    {
        if ($this->reflectionProperty === null) {
            $this->reflectionProperty = new \ReflectionProperty($entity, $this->name);
        }
        return $this->reflectionProperty;
    }
}