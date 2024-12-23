<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Exceptions\EntityException;
use Composite\Entity\Tests\TestStand\TestEntity;
use Composite\Entity\Tests\TestStand\TestEntityCollection;
use Composite\Entity\Tests\TestStand\TestStringCollection;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;

final class CollectionColumnTest extends \PHPUnit\Framework\TestCase
{
    public static function cast_dataProvider(): array
    {
        $sub1 = new TestEntity(str: 'foo');
        $sub2 = new TestEntity(str: 'bar');

        $collection1 = new TestEntityCollection();
        $collection1->push($sub1);

        $collection2 = new TestEntityCollection();
        $collection2->push($sub1);
        $collection2->push($sub2);

        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => '[]',
                'expected' => new TestEntityCollection(),
            ],
            [
                'value' => '[{"foo": fa}]',
                'expected' => null,
            ],
            [
                'value' => false,
                'expected' => null,
            ],
            [
                'value' => json_encode([$sub1]),
                'expected' => $collection1,
            ],
            [
                'value' => json_encode([$sub1, null, $sub2]),
                'expected' => $collection2,
            ],
        ];
    }

    #[DataProvider('cast_dataProvider')]
    public function test_entityCast(mixed $value, ?TestEntityCollection $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?TestEntityCollection $column = null,
            ) {}
        };
        $entity = $class::fromArray(['column' => $value]);
        $this->compareCollections($expected, $entity->column);
    }

    public static function stringCast_dataProvider(): array
    {
        $collection = new TestStringCollection();
        $collection[] = 'a';
        $collection[] = 'b';

        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => '[]',
                'expected' => new TestStringCollection(),
            ],
            [
                'value' => json_encode(['a', 'b']),
                'expected' => $collection,
            ],
        ];
    }

    #[DataProvider('stringCast_dataProvider')]
    public function test_stringCast(mixed $value, ?TestStringCollection $expected): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?TestStringCollection $column = null,
            ) {}
        };
        $entity = $class::fromArray(['column' => $value]);
        $this->compareCollections($expected, $entity->column);
    }

    public static function uncast_dataProvider(): array
    {
        $sub1 = new TestEntity(str: 'foo');
        $sub2 = new TestEntity(str: 'bar');

        $collection = new TestEntityCollection();
        $collection->push($sub1);
        $collection->push($sub2);


        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => new TestEntityCollection(),
                'expected' => '[]',
            ],
            [
                'value' => $collection,
                'expected' => json_encode([$sub1, $sub2]),
            ],
        ];
    }

    #[DataProvider('uncast_dataProvider')]
    public function test_entityUncast(?TestEntityCollection $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?TestEntityCollection $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertSame($expected, $actual);

        $newEntity = $entity::fromArray(['column' => $actual]);
        $this->compareCollections($newEntity->column, $value);
    }

    public static function stringUncast_dataProvider(): array
    {
        $collection = new TestStringCollection();
        $collection[] = 'a';
        $collection[] = 'b';

        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => new TestStringCollection(),
                'expected' => '[]',
            ],
            [
                'value' => $collection,
                'expected' => json_encode(['a', 'b']),
            ],
        ];
    }

    #[DataProvider('stringUncast_dataProvider')]
    public function test_stringUncast(?TestStringCollection $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?TestStringCollection $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertSame($expected, $actual);

        $newEntity = $entity::fromArray(['column' => $actual]);
        $this->compareCollections($newEntity->column, $value);
    }

    public function test_uncastException(): void
    {
        $sub = new TestEntity(float: INF);
        $collection = new TestEntityCollection();
        $collection[] = $sub;

        $entity = new class($collection) extends AbstractEntity {
            public function __construct(
                public TestEntityCollection $column,
            ) {}
        };
        $this->expectException(EntityException::class);
        $entity->toArray();
    }

    private function compareCollections(\SplDoublyLinkedList|ArrayCollection|null $expected, \SplDoublyLinkedList|ArrayCollection|null $actual): void
    {
        $compareExpected = $compareActual = null;
        if ($expected) {
            $compareExpected = [];
            foreach ($expected as $value) {
                if ($value instanceof AbstractEntity) {
                    $compareExpected[] = $value->toArray();
                } else {
                    $compareExpected[] = $value;
                }
            }
        }
        if ($actual) {
            $compareActual = [];
            foreach ($actual as $value) {
                if ($value instanceof AbstractEntity) {
                    $compareActual[] = $value->toArray();
                } else {
                    $compareActual[] = $value;
                }
            }
        }
        $this->assertSame($compareExpected, $compareActual);
    }
}