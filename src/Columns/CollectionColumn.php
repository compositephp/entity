<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Exceptions\EntityException;

class CollectionColumn extends AbstractColumn
{
    /**
     * @throws EntityException
     */
    public function cast(mixed $dbValue): \ArrayAccess
    {
        if (is_string($dbValue)) {
            try {
                $dbValue = (array)\json_decode($dbValue, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new EntityException($e->getMessage(), $e);
            }
        } elseif (!is_array($dbValue)) {
            throw new EntityException("Cannot to cast value for column {$this->name}, it must be string or array.");
        }
        /** @var \ArrayAccess $collection */
        $collection = new $this->type;
        foreach ($dbValue as $data) {
            if ($data === null) {
                continue;
            }
            $item = $this->subType->cast($data);
            $collection[] = $item;
        }
        return $collection;
    }

    /**
     * @param \Iterator|\IteratorAggregate $entityValue
     * @throws EntityException
     */
    public function uncast(mixed $entityValue): string
    {
        $list = [];
        foreach ($entityValue as $item) {
            if ($item instanceof AbstractEntity) {
                $list[] = $item->toArray();
            } else {
                $list[] = $this->subType->uncast($item);
            }
        }
        try {
            return \json_encode($list, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new EntityException($e->getMessage(), $e);
        }
    }
}