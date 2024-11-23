<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Tests\TestStand\TestUnitEnum;

final class UnitEnumColumnTest extends \PHPUnit\Framework\TestCase
{
    public static function cast_dataProvider(): array
    {
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => '',
                'expected' => null,
            ],
            [
                'value' => 'Foo',
                'expected' => TestUnitEnum::Foo,
            ],
            [
                'value' => 'Bar',
                'expected' => TestUnitEnum::Bar,
            ],
            [
                'value' => TestUnitEnum::Foo,
                'expected' => TestUnitEnum::Foo,
            ],
            [
                'value' => TestUnitEnum::Bar,
                'expected' => TestUnitEnum::Bar,
            ],
            [
                'value' => 'non-exist',
                'expected' => null,
            ],
        ];
    }

    /**
     * @dataProvider cast_dataProvider
     */
    public function test_cast(mixed $value, ?TestUnitEnum $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?TestUnitEnum $column = null,
            ) {}
        };
        $entity = $class::fromArray(['column' => $value]);
        $this->assertSame($expected, $entity->column);
    }

    public static function uncast_dataProvider(): array
    {
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => TestUnitEnum::Foo,
                'expected' => 'Foo',
            ],
            [
                'value' => TestUnitEnum::Bar,
                'expected' => 'Bar',
            ],
        ];
    }

    /**
     * @dataProvider uncast_dataProvider
     */
    public function test_uncast(mixed $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?TestUnitEnum $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertSame($expected, $actual);

        $newEntity = $entity::fromArray(['column' => $actual]);
        $newActual = $newEntity->toArray()['column'];
        $this->assertSame($entity->column, $newEntity->column);
        $this->assertSame($expected, $newActual);
    }
}