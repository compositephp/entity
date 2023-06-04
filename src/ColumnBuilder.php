<?php declare(strict_types=1);

namespace Composite\Entity;

use Composite\Entity\Columns;
use Composite\Entity\Exceptions\EntityException;

class ColumnBuilder
{
    /** @var array<string, class-string<Columns\AbstractColumn>> */
    private const PRIMITIVE_COLUMN_MAP = [
        'array' => Columns\ArrayColumn::class,
        'bool' => Columns\BoolColumn::class,
        'float' => Columns\FloatColumn::class,
        'int' => Columns\IntegerColumn::class,
        'string' => Columns\StringColumn::class,
        \stdClass::class => Columns\ObjectColumn::class,
        \DateTime::class => Columns\DateTimeColumn::class,
        \DateTimeImmutable::class => Columns\DateTimeColumn::class,
    ];

    /**
     * @param \ReflectionClass<AbstractEntity> $reflectionClass
     * @return Columns\AbstractColumn[]
     * @throws EntityException
     */
    public static function fromReflection(\ReflectionClass $reflectionClass): array
    {
        $result = $constructorColumns = $constructorDefaultValues = [];
        foreach ($reflectionClass->getConstructor()?->getParameters() ?? [] as $reflectionParameter) {
            $constructorColumns[$reflectionParameter->getName()] = true;
            if ($reflectionParameter->isPromoted() && $reflectionParameter->isDefaultValueAvailable()) {
                $constructorDefaultValues[$reflectionParameter->getName()] = $reflectionParameter->getDefaultValue();
            }
        }

        foreach ($reflectionClass->getProperties() as $property) {
            if ($property->isStatic() || $property->isPrivate()) {
                continue;
            }
            $type = $property->getType();
            if (!$type instanceof \ReflectionNamedType) {
                throw new EntityException("Property `{$property->name}` must have named type");
            }
            /** @var array<class-string, object> $propertyAttributes */
            $propertyAttributes = [];
            foreach ($property->getAttributes() as $attribute) {
                $attributeInstance = $attribute->newInstance();
                if ($attributeInstance instanceof Attributes\SkipSerialization) {
                    continue 2;
                }
                $propertyAttributes[$attributeInstance::class] = $attributeInstance;
            }

            if (isset($propertyAttributes[Attributes\ListOf::class])) {
                if ($type->getName() !== 'array') {
                    throw new EntityException("Property `{$property->name}` has ListOf attribute and must have array type.");
                }
                $columnClass = Columns\EntityListColumn::class;
                /** @var Attributes\ListOf $listOfAttribute */
                $listOfAttribute = $propertyAttributes[Attributes\ListOf::class];
                $typeName = $listOfAttribute->class;
            } else {
                $typeName = $type->getName();
                $columnClass = self::PRIMITIVE_COLUMN_MAP[$typeName] ?? null;

                if (!$columnClass && class_exists($typeName)) {
                    if (is_subclass_of($typeName, AbstractEntity::class)) {
                        $columnClass = Columns\EntityColumn::class;
                    } elseif (is_subclass_of($typeName, \BackedEnum::class)) {
                        $reflectionEnum = new \ReflectionEnum($typeName);
                        /** @var \ReflectionNamedType $backingType */
                        $backingType = $reflectionEnum->getBackingType();
                        if ($backingType->getName() === 'int') {
                            $columnClass = Columns\BackedIntEnumColumn::class;
                        } else {
                            $columnClass = Columns\BackedStringEnumColumn::class;
                        }
                    } elseif (is_subclass_of($typeName, \UnitEnum::class)) {
                        $columnClass = Columns\UnitEnumColumn::class;
                    } else {
                        if (in_array(CastableInterface::class, class_implements($typeName) ?: [])) {
                            $columnClass = Columns\CastableColumn::class;
                        }
                    }
                }
                if (!$columnClass) {
                    throw new EntityException("Type `{$property->getType()}` is not supported");
                }
            }

            if (array_key_exists($property->name, $constructorDefaultValues)) {
                $hasDefaultValue = true;
                $defaultValue = $constructorDefaultValues[$property->name];
            } elseif ($property->hasDefaultValue()) {
                $hasDefaultValue = true;
                $defaultValue = $property->getDefaultValue();
            } else {
                $hasDefaultValue = false;
                $defaultValue = null;
            }
            $result[$property->getName()] = new $columnClass(
                name: $property->getName(),
                type: $typeName,
                attributes: $propertyAttributes,
                hasDefaultValue: $hasDefaultValue,
                defaultValue: $defaultValue,
                isNullable: $type->allowsNull(),
                isReadOnly: $property->isReadOnly(),
                isConstructorPromoted: !empty($constructorColumns[$property->getName()]),
            );
        }
        return $result;
    }
}