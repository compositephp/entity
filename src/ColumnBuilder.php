<?php declare(strict_types=1);

namespace Composite\Entity;

use Composite\Entity\Exceptions\EntityException;
use Ramsey\Uuid\UuidInterface;

class ColumnBuilder
{
    /** @var array<string, class-string<Columns\AbstractColumn>> */
    private const PRIMITIVE_COLUMN_MAP = [
        'array' => Columns\ArrayColumn::class,
        'bool' => Columns\BoolColumn::class,
        'float' => Columns\FloatColumn::class,
        'int' => Columns\IntegerColumn::class,
        'string' => Columns\StringColumn::class,
        UuidInterface::class => Columns\UuidColumn::class,
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
            /** @var array<class-string, object> $propertyAttributes */
            $propertyAttributes = [];
            foreach ($property->getAttributes() as $attribute) {
                $attributeInstance = $attribute->newInstance();
                if ($attributeInstance instanceof Attributes\SkipSerialization) {
                    continue 2;
                }
                $propertyAttributes[$attributeInstance::class] = $attributeInstance;
            }
            $type = $property->getType();
            if (!$type instanceof \ReflectionNamedType) {
                throw new EntityException("Property `{$property->name}` must have named type");
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
            [
                'columnClass' => $columnClass,
                'type' => $typeName,
                'subType' => $subType,
            ] = self::getPropertyConfig($type->getName(), $propertyAttributes);

            $result[$property->getName()] = new $columnClass(
                name: $property->getName(),
                type: $typeName,
                subType: $subType,
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

    /**
     * @param array<class-string, object> $propertyAttributes
     * @return array{'columnClass': class-string, 'type': string, 'subType': string}
     */
    private static function getPropertyConfig(string $propertyTypeName, array $propertyAttributes = []): array
    {
        $columnClass = self::PRIMITIVE_COLUMN_MAP[$propertyTypeName] ?? null;
        $type = $propertyTypeName;
        $subType = null;

        if ($columnClass === Columns\ArrayColumn::class && isset($propertyAttributes[Attributes\ListOf::class])) {
            $columnClass = Columns\EntityListColumn::class;
            /** @var Attributes\ListOf $listOfAttribute */
            $listOfAttribute = $propertyAttributes[Attributes\ListOf::class];
            $type = $listOfAttribute->class;
            $subType = $listOfAttribute->keyColumn;
        } elseif (!$columnClass && class_exists($propertyTypeName)) {
            if (is_subclass_of($propertyTypeName, AbstractEntity::class)) {
                $columnClass = Columns\EntityColumn::class;
            } elseif (is_subclass_of($propertyTypeName, \BackedEnum::class)) {
                $reflectionEnum = new \ReflectionEnum($propertyTypeName);
                /** @var \ReflectionNamedType $backingType */
                $backingType = $reflectionEnum->getBackingType();
                if ($backingType->getName() === 'int') {
                    $columnClass = Columns\BackedIntEnumColumn::class;
                } else {
                    $columnClass = Columns\BackedStringEnumColumn::class;
                }
            } elseif (is_subclass_of($propertyTypeName, \UnitEnum::class)) {
                $columnClass = Columns\UnitEnumColumn::class;
            } else {
                $classInterfaces = array_fill_keys(class_implements($propertyTypeName), true);
                if (!empty($classInterfaces[CastableInterface::class])) {
                    $columnClass = Columns\CastableColumn::class;
                } elseif (!empty($classInterfaces[\ArrayAccess::class])
                    && (!empty($classInterfaces[\Iterator::class]) || !empty($classInterfaces[\IteratorAggregate::class]))) {
                    $columnClass = Columns\CollectionColumn::class;
                    $reflectionMethod = new \ReflectionMethod($propertyTypeName, 'offsetGet');
                    $returnType = $reflectionMethod->getReturnType();
                    [
                        'columnClass' => $collectionItemClass,
                        'type' => $collectionItemTypeName,
                    ] = self::getPropertyConfig($returnType->getName());
                    $subType = new $collectionItemClass(
                        name: $propertyTypeName,
                        type: $collectionItemTypeName,
                        subType: null,
                        attributes: [],
                        hasDefaultValue: false,
                        defaultValue: null,
                        isNullable: true,
                        isReadOnly: false,
                        isConstructorPromoted: false,
                    );
                }
            }
        }
        if (!$columnClass) {
            throw new EntityException("Type `{$propertyTypeName}` is not supported");
        }
        return ['columnClass' => $columnClass, 'type' => $type, 'subType' => $subType];
    }
}