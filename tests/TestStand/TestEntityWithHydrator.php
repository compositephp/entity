<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Attributes\Hydrator;

#[Hydrator(new TestHydrator)]
class TestEntityWithHydrator extends AbstractEntity
{
    public function __construct(
        public string $str,
        public int $int,
    ) {}
}