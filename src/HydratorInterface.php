<?php declare(strict_types=1);

namespace Composite\Entity;

interface HydratorInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): AbstractEntity;

    /**
     * @return array<string, mixed>
     */
    public function toArray(AbstractEntity $entity): array;
}