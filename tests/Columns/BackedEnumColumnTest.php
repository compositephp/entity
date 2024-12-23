<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Tests\TestStand\TestBackedStringEnum;
use PHPUnit\Framework\Attributes\DataProvider;

final class BackedEnumColumnTest extends \PHPUnit\Framework\TestCase
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
                'value' => TestBackedStringEnum::Foo->value,
                'expected' => TestBackedStringEnum::Foo,
            ],
            [
                'value' => TestBackedStringEnum::Bar->value,
                'expected' => TestBackedStringEnum::Bar,
            ],
            [
                'value' => TestBackedStringEnum::Foo,
                'expected' => TestBackedStringEnum::Foo,
            ],
            [
                'value' => TestBackedStringEnum::Bar,
                'expected' => TestBackedStringEnum::Bar,
            ],
            [
                'value' => 'non-exist',
                'expected' => null,
            ],
        ];
    }

    #[DataProvider('cast_dataProvider')]
    public function test_cast(mixed $value, ?TestBackedStringEnum $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?TestBackedStringEnum $column = null,
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
                'value' => TestBackedStringEnum::Foo,
                'expected' => TestBackedStringEnum::Foo->value,
            ],
            [
                'value' => TestBackedStringEnum::Bar,
                'expected' => TestBackedStringEnum::Bar->value,
            ],
        ];
    }

    #[DataProvider('uncast_dataProvider')]
    public function test_uncast(mixed $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?TestBackedStringEnum $column,
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