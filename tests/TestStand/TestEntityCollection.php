<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

class TestEntityCollection extends \SplDoublyLinkedList
{
    public function offsetGet($index): ?TestEntity
    {
        parent::offsetGet($index);
    }
}