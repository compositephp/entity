<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

use Composite\Entity\AbstractEntity;

#[TestAttribute(2)]
#[TestAttribute(1)]
class TesEntityWithAttribute extends AbstractEntity
{
    public function __construct(
        public string $str = 'foo',
    ) {}
}