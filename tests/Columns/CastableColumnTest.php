<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Tests\TestStand\TestCastableIntObject;
use PHPUnit\Framework\Attributes\DataProvider;

final class CastableColumnTest extends \PHPUnit\Framework\TestCase
{
    public static function cast_dataProvider(): array
    {
        $date = '2020-01-01 01:02:03';
        $unixTime = strtotime($date);
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => 0,
                'expected' => null,
            ],
            [
                'value' => 'abc',
                'expected' => null,
            ],
            [
                'value' => (string)$unixTime,
                'expected' => new TestCastableIntObject($unixTime),
            ],
            [
                'value' => $unixTime,
                'expected' => new TestCastableIntObject($unixTime),
            ],
        ];
    }

    #[DataProvider('cast_dataProvider')]
    public function test_cast(mixed $value, ?TestCastableIntObject $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?TestCastableIntObject $column = null,
            ) {}
        };
        $entity = $class::fromArray(['column' => $value]);
        $this->assertSame($expected?->format('Uu'), $entity->column?->format('Uu'));
    }

    public static function uncast_dataProvider(): array
    {
        $date = '2020-01-01 01:02:03';
        $unixTime = strtotime($date);
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => new TestCastableIntObject($unixTime),
                'expected' => $unixTime,
            ],
        ];
    }

    #[DataProvider('uncast_dataProvider')]
    public function test_uncast(mixed $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?TestCastableIntObject $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertSame($expected, $actual);

        $newEntity = $entity::fromArray(['column' => $actual]);
        $newActual = $newEntity->toArray()['column'];
        $this->assertSame($entity->column?->format('Uu'), $newEntity->column?->format('Uu'));
        $this->assertSame($expected, $newActual);
    }
}