<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use PHPUnit\Framework\Attributes\DataProvider;

final class StringColumnTest extends \PHPUnit\Framework\TestCase
{
    public static function cast_dataProvider(): array
    {
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => 0,
                'expected' => '0',
            ],
            [
                'value' => 123,
                'expected' => '123',
            ],
            [
                'value' => '123',
                'expected' => '123',
            ],
            [
                'value' => 'abc',
                'expected' => 'abc',
            ],
        ];
    }

    #[DataProvider('cast_dataProvider')]
    public function test_cast(mixed $value, ?string $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?string $column = null,
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
                'value' => '',
                'expected' => '',
            ],
            [
                'value' => 'abc',
                'expected' => 'abc',
            ],
            [
                'value' => '123',
                'expected' => '123',
            ],
        ];
    }

    #[DataProvider('uncast_dataProvider')]
    public function test_uncast(mixed $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?string $column,
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