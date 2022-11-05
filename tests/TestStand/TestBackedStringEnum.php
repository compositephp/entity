<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

enum TestBackedStringEnum: string
{
    case Foo = 'foo';
    case Bar = 'bar';
}