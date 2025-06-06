<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use PHPUnit\Framework\Attributes\DataProvider;

final class BoolColumnTest extends \PHPUnit\Framework\TestCase
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
                'expected' => false,
            ],
            [
                'value' => true,
                'expected' => true,
            ],
            [
                'value' => 1,
                'expected' => true,
            ],
            [
                'value' => '1',
                'expected' => true,
            ],
            [
                'value' => 999,
                'expected' => true,
            ],
            [
                'value' => '999',
                'expected' => true,
            ],
            [
                'value' => 'true',
                'expected' => true,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
            [
                'value' => 0,
                'expected' => false,
            ],
            [
                'value' => '0',
                'expected' => false,
            ],
            [
                'value' => 'false',
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('cast_dataProvider')]
    public function test_cast(mixed $value, ?bool $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?bool $column = null,
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
                'value' => true,
                'expected' => true,
            ],
            [
                'value' => false,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('uncast_dataProvider')]
    public function test_uncast(mixed $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?bool $column,
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