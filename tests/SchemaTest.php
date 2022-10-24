<?php declare(strict_types=1);

namespace Composite\Entity\Tests;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Schema;

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
}