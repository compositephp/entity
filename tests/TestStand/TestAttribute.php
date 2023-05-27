<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class TestAttribute
{
    public function __construct(
        public readonly int $val,
    ){}
}