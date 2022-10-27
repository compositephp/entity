<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

use Composite\Entity\AbstractEntity;

class TestEntity extends AbstractEntity
{
    public function __construct(
        public string $str = 'foo',
        public int $int = 999,
        public float $float = 9.99,
        public bool $bool = true,
        public array $arr = [],
        public \stdClass $object = new \stdClass(),
        public \DateTime $date_time = new \DateTime(),
        public \DateTimeImmutable $date_time_immutable = new \DateTimeImmutable(),
        public TestBackedStringEnum $backed_enum = TestBackedStringEnum::Foo,
        public TestUnitEnum $unit_enum = TestUnitEnum::Bar,
        public TestSubEntity $entity = new TestSubEntity(),
        public TestCastableIntObject $castable = new TestCastableIntObject(946684801) //2000-01-01 00:00:01
    ) {}
}