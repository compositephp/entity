<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

enum TestUnitEnum
{
    case Foo;
    case Bar;

    public static function getCycleMigrationValue(): array
    {
        return array_map(fn($enum) => $enum->name, self::cases());
    }
}