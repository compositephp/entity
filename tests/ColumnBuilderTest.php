<?php declare(strict_types=1);

namespace Composite\Entity\Tests;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Attributes\ListOf;
use Composite\Entity\Attributes\SkipSerialization;
use Composite\Entity\Columns\AbstractColumn;
use Composite\Entity\Tests\TestStand\TestSubEntity;
use PHPUnit\Framework\Attributes\DataProvider;

final class ColumnBuilderTest extends \PHPUnit\Framework\TestCase
{
    public static function visibility_dataProvider(): array
    {
        return [
            [
                'entity' => new class extends AbstractEntity {
                    public int $pub2 = 2;
                    protected int $prot2 = 2;
                    private int $prv2 = 2;

                    public function __construct(
                        public int $pub1 = 1,
                        protected int $prot1 = 1,
                        private int $prv1 = 1,
                        int $custom = 999,
                    ) {}
                },
                'expected' => ['pub1', 'prot1', 'pub2', 'prot2']
            ],
            [
                'entity' => new class extends AbstractEntity {
                    public int $var4 = 4;
                    #[SkipSerialization]
                    public int $var5 = 5;
                    protected int $var6 = 6;
                    #[SkipSerialization]
                    protected int $var7 = 7;
                    private int $var8 = 8;

                    public function __construct(
                        public int $var1 = 1,
                        #[SkipSerialization]
                        public int $var2 = 2,
                        #[SkipSerialization]
                        protected int $var3 = 3,
                    ) {}
                },
                'expected' => ['var1', 'var4', 'var6',]
            ],
        ];
    }

    #[DataProvider('visibility_dataProvider')]
    public function test_visibility(AbstractEntity $entity, array $expected): void
    {
        $schema = $entity::schema();
        foreach ($expected as $name) {
            $this->assertNotNull($schema->getColumn($name));
            $this->assertSame($name, $schema->getColumn($name)?->name);
        }
        $columnNames = array_map(fn (AbstractColumn $column): string => $column->name, $schema->columns);
        foreach ($columnNames as $columnName) {
            $this->assertContains($columnName, $expected);
        }
    }

    public static function type_dataProvider(): array
    {
        return [
            [
                'entity' => new TestStand\TestEntity(),
                'expected' => [
                    'str' => 'string',
                    'int' => 'int',
                    'float' => 'float',
                    'bool' => 'bool',
                    'arr' => 'array',
                    'object' => \stdClass::class,
                    'date_time' => \DateTime::class,
                    'date_time_immutable' => \DateTimeImmutable::class,
                    'backed_enum' => TestStand\TestBackedStringEnum::class,
                    'unit_enum' => TestStand\TestUnitEnum::class,
                    'entity' => TestStand\TestSubEntity::class,
                    'castable' => TestStand\TestCastableIntObject::class,
                ]
            ],
        ];
    }

    #[DataProvider('type_dataProvider')]
    public function test_type(AbstractEntity $entity, array $expected): void
    {
        $schema = $entity::schema();
        foreach ($expected as $name => $expectedType) {
            $this->assertNotNull($schema->getColumn($name));
            $this->assertSame($expectedType, $schema->getColumn($name)?->type);
        }
    }

    public static function hasDefaultValue_dataProvider(): array
    {
        return [
            [
                'class' => new class(str1: 'string') extends AbstractEntity {
                    public string $str3;
                    public string $str4 = 'bar';
                    public function __construct(
                        public string $str1,
                        public string $str2 = 'foo',
                    ) {}
                },
                'expected' => [
                    'str1' => false,
                    'str2' => true,
                    'str3' => false,
                    'str4' => true,
                ]
            ],
        ];
    }

    #[DataProvider('hasDefaultValue_dataProvider')]
    public function test_hasDefaultValue(AbstractEntity $class, array $expected): void
    {
        $schema = $class::schema();
        foreach ($expected as $name => $expectedHasDefaultValue) {
            $this->assertNotNull($schema->getColumn($name));
            $this->assertSame($expectedHasDefaultValue, $schema->getColumn($name)?->hasDefaultValue);
        }
    }

    public static function defaultValue_dataProvider(): array
    {
        $entity = new TestStand\TestEntity (
            str: 'str_',
            int: -1,
            float: -1.1,
            bool: false,
            arr: [1, 2, 3],
            backed_enum: TestStand\TestBackedStringEnum::Bar,
            unit_enum: TestStand\TestUnitEnum::Foo,
        );
        return [
            [
                'entity' => $entity,
                'expected' => [
                    'str' => 'foo',
                    'int' => 999,
                    'float' => 9.99,
                    'bool' => true,
                    'arr' => [],
                    'object' => $entity->object,
                    'date_time' => $entity->date_time,
                    'date_time_immutable' => $entity->date_time_immutable,
                    'backed_enum' => TestStand\TestBackedStringEnum::Foo,
                    'unit_enum' => TestStand\TestUnitEnum::Bar,
                    'entity' => $entity->entity,
                    'castable' => $entity->castable,
                ]
            ],
        ];
    }

    #[DataProvider('defaultValue_dataProvider')]
    public function test_defaultValue(AbstractEntity $entity, array $expected): void
    {
        $schema = $entity::schema();
        foreach ($expected as $name => $expectedDefaultValue) {
            $this->assertNotNull($schema->getColumn($name));
            $this->assertTrue($schema->getColumn($name)?->hasDefaultValue);

            $actualDefaultValue = $schema->getColumn($name)?->defaultValue;
            if ($expectedDefaultValue instanceof \DateTimeInterface) {
                $this->assertSame(0, (int)$expectedDefaultValue->diff($actualDefaultValue)->format('%i'));
            } elseif ($expectedDefaultValue instanceof \stdClass) {
                $this->assertSame((array)$expectedDefaultValue, (array)$actualDefaultValue);
            } elseif ($expectedDefaultValue instanceof AbstractEntity) {
                $this->assertSame($expectedDefaultValue->toArray(), $actualDefaultValue?->toArray());
            }  else {
                $this->assertSame($expectedDefaultValue, $actualDefaultValue, "Column `$name` has wrong default value");
            }
        }
    }

    public static function isNullable_dataProvider(): array
    {
        return [
            [
                'class' => new class extends AbstractEntity {
                    public string $foo2 = 'foo';
                    public ?string $bar2 = null;

                    public function __construct(
                        public string $foo1 = 'foo',
                        public ?string $bar1 = null,
                    ) {}
                },
                'expected' => [
                    'foo1' => false,
                    'bar1' => true,
                    'foo2' => false,
                    'bar2' => true,
                ]
            ],
        ];
    }

    #[DataProvider('isNullable_dataProvider')]
    public function test_isNullable(AbstractEntity $class, array $expected): void
    {
        $schema = $class::schema();
        foreach ($expected as $name => $expectedIsNullable) {
            $this->assertNotNull($schema->getColumn($name));
            $this->assertSame($expectedIsNullable, $schema->getColumn($name)?->isNullable);
        }
    }

    public static function isConstructorPromoted_dataProvider(): array
    {
        return [
            [
                'entity' => new class extends AbstractEntity {
                    public string $foo2 = 'foo';
                    protected ?string $bar2;

                    public function __construct(
                        public string $foo1 = 'foo',
                        protected ?string $bar1 = null,
                    ) {}
                },
                'expected' => [
                    'foo1' => true,
                    'bar1' => true,
                    'foo2' => false,
                    'bar2' => false,
                ]
            ],
        ];
    }

    #[DataProvider('isConstructorPromoted_dataProvider')]
    public function test_isConstructorPromoted(AbstractEntity $entity, array $expected): void
    {
        $schema = $entity::schema();
        foreach ($expected as $name => $expectedIsConstructorPromoted) {
            $this->assertNotNull($schema->getColumn($name));
            $this->assertSame($expectedIsConstructorPromoted, $schema->getColumn($name)?->isConstructorPromoted);
        }
    }

    public function test_isReadOnly(): void
    {
        $entity = new class extends AbstractEntity {
            public string $foo2 = 'foo';
            public readonly ?string $bar2;

            public function __construct(
                public string $foo1 = 'foo',
                public readonly ?string $bar1 = null,
            ) {
            }
        };

        $expected = [
            'foo1' => false,
            'bar1' => true,
            'foo2' => false,
            'bar2' => true,
        ];

        $schema = $entity::schema();
        foreach ($expected as $name => $expectedIsReadOnly) {
            $this->assertNotNull($schema->getColumn($name));
            $this->assertSame($expectedIsReadOnly, $schema->getColumn($name)?->isReadOnly);
        }
    }

    public static function notSupported_dataProvider(): array
    {
        return [
            [
                new class extends AbstractEntity {
                    public readonly int $id;
                    public $foo2 = 'foo';
                }
            ],
            [
                new class extends AbstractEntity {
                    public readonly int $id;
                    #[ListOf(TestSubEntity::class)]
                    public string $foo2 = 'foo';
                }
            ],
            [
                new class extends AbstractEntity {
                    public readonly int $id;
                    public \ReflectionClass $foo2;
                }
            ],
        ];
    }

    #[DataProvider('notSupported_dataProvider')]
    public function test_notSupported(AbstractEntity $entity): void
    {
        try {
            $entity::schema();
            $this->assertTrue(false);
        } catch (\Exception) {
            $this->assertTrue(true);
        }
    }

    public function test_virtualProperty(): void
    {
        $class = new class extends AbstractEntity {
            public string $foo {get => 'foo';}
            public string $bar = 'bar';
        };
        $schema = $class::schema();
        $this->assertEmpty($schema->getColumn('foo'));
    }
}