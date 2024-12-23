<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Exceptions\EntityException;
use PHPUnit\Framework\Attributes\DataProvider;

final class ArrayColumnTest extends \PHPUnit\Framework\TestCase
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
                'value' => '[]',
                'expected' => [],
            ],
            [
                'value' => '{}',
                'expected' => [],
            ],
            [
                'value' => 'abc',
                'expected' => null,
            ],
            [
                'value' => '[1,2,3]',
                'expected' => [1, 2, 3],
            ],
            [
                'value' => '{"foo": "bar", "int": 123}',
                'expected' => ['foo' => 'bar', 'int' => 123],
            ],
            [
                'value' => ['foo' => 'bar', 'int' => 123],
                'expected' => ['foo' => 'bar', 'int' => 123],
            ],
        ];
    }

    #[DataProvider('cast_dataProvider')]
    public function test_cast(mixed $value, ?array $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?array $column = null,
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
                'value' => [],
                'expected' => '[]',
            ],
            [
                'value' => [1, 2, 3],
                'expected' => '[1,2,3]',
            ],
            [
                'value' => ['foo' => 'bar', 'int' => 1, 'bool' => true],
                'expected' => '{"foo":"bar","int":1,"bool":true}',
            ],
        ];
    }

    #[DataProvider('uncast_dataProvider')]
    public function test_uncast(mixed $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?array $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertSame($expected, $actual);

        $newEntity = $entity::fromArray(['column' => $actual]);
        $newActual = $newEntity->toArray()['column'];
        $this->assertSame($entity->column, $newEntity->column);
        $this->assertSame($expected, $newActual);
    }

    public function test_exception(): void
    {
        $entity = new class([INF]) extends AbstractEntity {
            public function __construct(
                public array $column,
            ) {}
        };
        try {
            $entity->toArray();
            $this->assertTrue(false);
        } catch (EntityException) {
            $this->assertTrue(true);
        }
    }
}