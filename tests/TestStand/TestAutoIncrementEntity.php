<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

use Composite\Entity\AbstractEntity;

class TestAutoIncrementEntity extends AbstractEntity
{
    public readonly int $id;

    public function __construct(
        public readonly string $email,
        public string $name,
        public ?int $age = null,
        public bool $is_test = false,
    ) {}
}