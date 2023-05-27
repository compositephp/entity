<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Attributes\ListOf;
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
}