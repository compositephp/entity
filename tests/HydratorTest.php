<?php declare(strict_types=1);

namespace Composite\Entity\Tests;

use Composite\Entity\Tests\TestStand\TestEntityWithHydrator;

final class HydratorTest extends \PHPUnit\Framework\TestCase
{
    public function test_fromAray(): void
    {
        $entity = TestEntityWithHydrator::fromArray([
            'str' => 'foo',
            'int' => '123',
        ]);
        $this->assertEquals('_foo_', $entity->str);
        $this->assertEquals(123, $entity->int);

        $array = $entity->toArray();
        $this->assertEquals('foo', $array['str']);
        $this->assertEquals(123, $array['int']);
    }
}