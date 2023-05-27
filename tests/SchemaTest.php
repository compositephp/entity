<?php declare(strict_types=1);

namespace Composite\Entity\Tests;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Exceptions\EntityException;
use Composite\Entity\Schema;
use Composite\Entity\Tests\TestStand\TesEntityWithAttribute;
use Composite\Entity\Tests\TestStand\TestAttribute;
use Composite\Entity\Tests\TestStand\TestBackedIntEnum;

final class SchemaTest extends \PHPUnit\Framework\TestCase
{
    public function test_build(): void
    {
        $class = new class extends AbstractEntity {
            public readonly int $id;
            public function __construct(
                public string $str = 'abc',
                public int $number = 123,
                private readonly \DateTimeImmutable $dt = new \DateTimeImmutable(),
            ) {}

            public function getDt(): \DateTimeImmutable
            {
                return $this->dt;
            }
        };
        $schema = Schema::build($class::class);
        $this->assertCount(3, $schema->columns);
        $this->assertSame($class::class, $schema->class);
        $this->assertSame(
            [
                $schema->getColumn('id'),
            ],
            array_values($schema->getNonConstructorColumns())
        );
        $this->assertSame(
            [
                $schema->getColumn('str'),
                $schema->getColumn('number'),
            ],
            array_values($schema->getConstructorColumns())
        );
    }

    public function test_castData(): void
    {
        $class = new class extends AbstractEntity {
            public TestBackedIntEnum $var1;
            public ?TestBackedIntEnum $var2;
        };
        $this->assertEquals([], $class->toArray());

        $loaded = $class::fromArray(['var1' => TestBackedIntEnum::FooInt->value, 'var2' => TestBackedIntEnum::BarInt->value]);
        $this->assertSame(TestBackedIntEnum::FooInt, $loaded->var1);
        $this->assertSame(TestBackedIntEnum::BarInt, $loaded->var2);

        $loaded = $class::fromArray(['var1' => '123', 'var2' => 'no']);
        $this->assertSame(TestBackedIntEnum::FooInt, $loaded->var1);
        $this->assertSame(null, $loaded->var2);

        try {
            $failed = $class::fromArray(['var1' => 'no', 'var2' => 'no']);
            $this->assertFalse(true);
        } catch (EntityException) {}

        $this->assertFalse(isset($failed));
    }

    public function test_getAttribute(): void
    {
        $attribute = TesEntityWithAttribute::schema()->getFirstAttributeByClass(TestAttribute::class);
        $this->assertNotNull($attribute);
        $this->assertEquals(2, $attribute->val);
    }
}