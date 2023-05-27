<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Attributes\ListOf;
use Composite\Entity\Exceptions\EntityException;
use Composite\Entity\Tests\TestStand\TestEntity;
use Composite\Entity\Tests\TestStand\TestSubEntity;

final class EntityListColumnTest extends \PHPUnit\Framework\TestCase
{
    public function cast_dataProvider(): array
    {
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => '',
                'expected' => null
            ],
            [
                'value' => '[]',
                'expected' => []
            ],
            [
                'value' => '[1,"a",false]',
                'expected' => []
            ],
            [
                'value' => '[[],2,3]',
                'expected' => [
                    TestSubEntity::fromArray([]),
                ],
            ],
            [
                'value' => '[[],{"str": "John"}]',
                'expected' => [
                    TestSubEntity::fromArray([]),
                    TestSubEntity::fromArray(['str' => 'John']),
                ],
            ],
            [
                'value' => '[{"str": "John", "number": 1},0,{"str": "Snow", "number": false}]',
                'expected' => [
                    TestSubEntity::fromArray(['str' => 'John', 'number' => 1]),
                    TestSubEntity::fromArray(['str' => 'Snow', 'number' => 0]),
                ],
            ],
            [
                'value' => [
                    TestSubEntity::fromArray(['str' => 'John', 'number' => 1]),
                    12321
                ],
                'expected' => [
                    TestSubEntity::fromArray(['str' => 'John', 'number' => 1]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider cast_dataProvider
     */
    public function test_cast(mixed $value, ?array $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                #[ListOf(TestSubEntity::class)]
                public ?array $column = null,
            ) {}
        };
        $entity = $class::fromArray(['column' => $value]);
        $this->assertEquals($expected, $entity->column);

        $attribute = $entity::schema()->getColumn('column')->getFirstAttributeByClass(ListOf::class);
        $this->assertNotNull($attribute);
        $this->assertInstanceOf(ListOf::class, $attribute);
    }

    public function uncast_dataProvider(): array
    {
        $sub1 = TestSubEntity::fromArray(['str' => 'foo', 'number' => 123]);
        $sub2 = TestSubEntity::fromArray(['str' => 'bar', 'number' => 456]);
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
                'value' => [$sub1, $sub2],
                'expected' => '[' . json_encode($sub1) . ',' . json_encode($sub2) .']',
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
                #[ListOf(TestSubEntity::class)]
                public ?array $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertEquals($expected, $actual);

        $newEntity = $entity::fromArray(['column' => $actual]);
        $newActual = $newEntity->toArray()['column'];
        $this->assertEquals($entity->column, $newEntity->column);
        $this->assertEquals($expected, $newActual);
    }


    public function test_castException(): void
    {
        $entity = new class([]) extends AbstractEntity {
            public function __construct(
                #[ListOf(TestEntity::class)]
                public array $column,
            ) {}
        };
        try {
            $entity::fromArray(['column' => false]);
            $this->assertTrue(false);
        } catch (EntityException) {
            $this->assertTrue(true);
        }
    }

    public function test_uncastException(): void
    {
        $sub = new TestEntity(float: INF);

        $entity = new class([$sub]) extends AbstractEntity {
            public function __construct(
                #[ListOf(TestEntity::class)]
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


    public function test_exception(): void
    {
        $entity = new class(new TestEntity(float: INF)) extends AbstractEntity {
            public function __construct(
                public TestEntity $column,
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