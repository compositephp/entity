<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

class TestStringCollection extends \Doctrine\Common\Collections\ArrayCollection
{
    public function offsetGet($offset): ?string
    {
        parent::offsetGet($offset);
    }
}