<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\Exceptions\EntityException;

class BackedIntEnumColumn extends AbstractColumn
{
    /**
     * @throws EntityException
     */
    public function cast(mixed $dbValue): \BackedEnum
    {
        /** @var class-string<\BackedEnum> $enumClass */
        $enumClass = $this->type;
        if ($dbValue instanceof $enumClass) {
            return $dbValue;
        }
        if (!is_numeric($dbValue)) {
            throw new EntityException("Cannot to cast value for column {$this->name} and enum `$enumClass`, it must be integer.");
        }
        return $enumClass::from((int)$dbValue);
    }

    /**
     * @param \BackedEnum $entityValue
     */
    public function uncast(mixed $entityValue): int
    {
        return $entityValue->value;
    }
}