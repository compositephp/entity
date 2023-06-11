<?php declare(strict_types=1);

namespace Composite\Entity\Tests\TestStand;

use Composite\Entity\AbstractEntity;

class TestHydrator implements \Composite\Entity\HydratorInterface
{
    public function fromArray(array $data): AbstractEntity
    {
        return new TestEntityWithHydrator(
            str: '_' . $data['str'] . '_',
            int: (int)$data['int'],
        );
    }

    public function toArray(AbstractEntity|TestEntityWithHydrator $entity): array
    {
        return [
            'str' => trim($entity->str, '_'),
            'int' => $entity->int,
        ];
    }
}