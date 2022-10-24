<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

enum TestBackedIntEnum: int
{
    case FooInt = 123;
    case BarInt = 456;
}