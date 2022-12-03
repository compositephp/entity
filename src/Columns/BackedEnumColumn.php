<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\Exceptions\EntityException;

class BackedEnumColumn extends AbstractColumn
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
        if (is_numeric($dbValue)) {
            $dbValue = intval($dbValue);
        } elseif (!is_string($dbValue)) {
            throw new EntityException("Cannot to cast value for column {$this->name} and enum `$enumClass`, it must be string or integer.");
        }
        return $enumClass::from($dbValue);
    }

    /**
     * @param \BackedEnum $entityValue
     */
    public function uncast(mixed $entityValue): int|string
    {
        return $entityValue->value;
    }
}