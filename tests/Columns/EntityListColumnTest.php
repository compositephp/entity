<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Attributes\ListOf;
use Composite\Entity\Exceptions\EntityException;
use Composite\Entity\Tests\TestStand\TestBackedStringEnum;
use Composite\Entity\Tests\TestStand\TestEntity;
use Composite\Entity\Tests\TestStand\TestSubEntity;
use Composite\Entity\Tests\TestStand\TestUnitEnum;

final class EntityListColumnTest extends \PHPUnit\Framework\TestCase
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
                'value' => '[null,[],2,3]',
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

    public static function castWithKey_dataProvider(): array
    {
        return [
            [
                'value' => '[[],{"str": "John"}]',
                'expected' => [
                    'foo' => TestSubEntity::fromArray([]),
                    'John' => TestSubEntity::fromArray(['str' => 'John']),
                ],
            ],
            [
                'value' => '[{"str": "John", "number": 1},0,{"str": "Snow", "number": false}]',
                'expected' => [
                    'John' => TestSubEntity::fromArray(['str' => 'John', 'number' => 1]),
                    'Snow' => TestSubEntity::fromArray(['str' => 'Snow', 'number' => 0]),
                ],
            ],
        ];
    }

    /**
     * @dataProvider castWIthKey_dataProvider
     */
    public function test_castWithKey(mixed $value, ?array $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                #[ListOf(TestSubEntity::class, 'str')]
                public ?array $column = null,
            ) {}
        };
        $entity = $class::fromArray(['column' => $value]);
        $this->assertEquals($expected, $entity->column);

        $attribute = $entity::schema()->getColumn('column')->getFirstAttributeByClass(ListOf::class);
        $this->assertNotNull($attribute);
        $this->assertInstanceOf(ListOf::class, $attribute);
    }

    public function test_castWithUnitEnumKey(): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                #[ListOf(TestEntity::class, 'unit_enum')]
                public ?array $column = null,
            ) {}
        };

        $entity = $class::fromArray([
            'column' => [
                TestUnitEnum::Foo->name => ['str' => 'UnitFoo', 'unit_enum' => TestUnitEnum::Foo->name],
                TestUnitEnum::Bar->name => ['str' => 'UnitBar', 'unit_enum' => TestUnitEnum::Bar->name],
            ]
        ]);
        $this->assertCount(2, $entity->column);
        $this->assertEquals( 'UnitFoo', $entity->column[TestUnitEnum::Foo->name]->str);
        $this->assertEquals('UnitBar', $entity->column[TestUnitEnum::Bar->name]->str);
    }

    public function test_castWithBackedEnumKey(): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                #[ListOf(TestEntity::class, 'backed_enum')]
                public ?array $column = null,
            ) {}
        };

        $entity = $class::fromArray([
            'column' => [
                TestBackedStringEnum::Foo->value => ['str' => 'BackedFoo', 'backed_enum' => TestBackedStringEnum::Foo->value],
                TestBackedStringEnum::Bar->value => ['str' => 'BackedBar', 'backed_enum' => TestBackedStringEnum::Bar->value],
            ]
        ]);
        $this->assertCount(2, $entity->column);
        $this->assertEquals( 'BackedFoo', $entity->column[TestBackedStringEnum::Foo->value]->str);
        $this->assertEquals('BackedBar', $entity->column[TestBackedStringEnum::Bar->value]->str);
    }

    public static function uncast_dataProvider(): array
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

    public static function uncastWithKey_dataProvider(): array
    {
        $sub1 = TestSubEntity::fromArray(['str' => 'foo', 'number' => 123]);
        $sub2 = TestSubEntity::fromArray(['str' => 'bar', 'number' => 456]);
        return [
            [
                'value' => ['foo' => $sub1, 'bar' => $sub2],
                'expected' => '{"foo":' . json_encode($sub1) . ',"bar":' . json_encode($sub2) .'}',
            ],
            [
                'value' => [$sub1, $sub2],
                'expected' => '{"foo":' . json_encode($sub1) . ',"bar":' . json_encode($sub2) .'}',
            ],
        ];
    }

    /**
     * @dataProvider uncastWithKey_dataProvider
     */
    public function test_uncastWithKey(mixed $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                #[ListOf(TestSubEntity::class, 'str')]
                public ?array $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertEquals($expected, $actual);
    }

    public function test_uncastWithUnitEnumKey(): void
    {
        $value = [
            TestUnitEnum::Foo->name => new TestEntity(str: 'UnitFoo', unit_enum: TestUnitEnum::Foo),
            TestUnitEnum::Bar->name => new TestEntity(str: 'UnitBar', unit_enum: TestUnitEnum::Bar),
        ];
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                #[ListOf(TestEntity::class, 'unit_enum')]
                public array $column,
            ) {}
        };
        $data = json_decode($entity->toArray()['column'], true);
        
        $this->assertEquals('UnitFoo', $data[TestUnitEnum::Foo->name]['str']);
        $this->assertEquals(TestUnitEnum::Foo->name, $data[TestUnitEnum::Foo->name]['unit_enum']);
        $this->assertEquals('UnitBar', $data[TestUnitEnum::Bar->name]['str']);
        $this->assertEquals(TestUnitEnum::Bar->name, $data[TestUnitEnum::Bar->name]['unit_enum']);
    }
    
    public function test_uncastWithBackedEnumKey(): void
    {
        $value = [
            TestBackedStringEnum::Foo->value => new TestEntity(str: 'BackedFoo', backed_enum: TestBackedStringEnum::Foo),
            TestBackedStringEnum::Bar->value => new TestEntity(str: 'BackedBar', backed_enum: TestBackedStringEnum::Bar),
        ];
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                #[ListOf(TestEntity::class, 'backed_enum')]
                public array $column,
            ) {}
        };
        $data = json_decode($entity->toArray()['column'], true);
        
        $this->assertEquals('BackedFoo', $data[TestBackedStringEnum::Foo->value]['str']);
        $this->assertEquals(TestBackedStringEnum::Foo->value, $data[TestBackedStringEnum::Foo->value]['backed_enum']);
        $this->assertEquals('BackedBar', $data[TestBackedStringEnum::Bar->value]['str']);
        $this->assertEquals(TestBackedStringEnum::Bar->value, $data[TestBackedStringEnum::Bar->value]['backed_enum']);
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
        $this->expectException(EntityException::class);
        $entity->toArray();
    }


    public function test_exception(): void
    {
        $entity = new class(new TestEntity(float: INF)) extends AbstractEntity {
            public function __construct(
                public TestEntity $column,
            ) {}
        };
        $this->expectException(EntityException::class);
        $entity->toArray();
    }
}