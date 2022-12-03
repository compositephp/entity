<?php declare(strict_types=1);

namespace Composite\Entity\Attributes;

#[\Attribute]
class ListOf
{
    public function __construct(
        public readonly string $class,
    ){}
}