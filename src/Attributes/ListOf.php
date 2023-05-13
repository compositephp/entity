<?php declare(strict_types=1);

namespace Composite\Entity\Attributes;

#[\Attribute(\Attribute::TARGET_PARAMETER | \Attribute::TARGET_PROPERTY)]
class ListOf
{
    public function __construct(
        public readonly string $class,
    ){}
}