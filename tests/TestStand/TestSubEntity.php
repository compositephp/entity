<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

use Composite\Entity\AbstractEntity;

class TestSubEntity extends AbstractEntity
{
    public function __construct(
        public string $str = 'foo',
        public int $number = 123,
    ) {}
}