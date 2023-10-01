<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class UuidColumnTest extends \PHPUnit\Framework\TestCase
{
    public static function cast_dataProvider(): array
    {
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'expected' => Uuid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8'),
            ],
            [
                'value' => '550e8400-e29b-41d4-a716-446655440000',
                'expected' => Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'),
            ],
            [
                'value' => 'invalid_uuid',
                'expected' => null,
            ],
        ];
    }

    /**
     * @dataProvider cast_dataProvider
     */
    public function test_cast(mixed $value, ?UuidInterface $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?UuidInterface $column = null,
            ) {}
        };
        $entity = $class::fromArray(['column' => $value]);
        $this->assertEquals($expected, $entity->column);
    }

    public static function uncast_dataProvider(): array
    {
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => Uuid::fromString('6ba7b810-9dad-11d1-80b4-00c04fd430c8'),
                'expected' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            [
                'value' => Uuid::fromString('550e8400-e29b-41d4-a716-446655440000'),
                'expected' => '550e8400-e29b-41d4-a716-446655440000',
            ],
        ];
    }

    /**
     * @dataProvider uncast_dataProvider
     */
    public function test_uncast(?UuidInterface $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?UuidInterface $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertEquals($expected, $actual);

        $newEntity = $entity::fromArray(['column' => $actual]);
        $newActual = $newEntity->toArray()['column'];
        $this->assertEquals($entity->column, $newEntity->column);
        $this->assertEquals($expected, $newActual);
    }
}