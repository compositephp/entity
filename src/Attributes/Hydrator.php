<?php declare(strict_types=1);

namespace Composite\Entity\Attributes;

use Composite\Entity\HydratorInterface;

#[\Attribute(\Attribute::TARGET_CLASS)]
class Hydrator
{
    public function __construct(
        public readonly HydratorInterface $hydrator,
    ) {}
}