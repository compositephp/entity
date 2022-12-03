<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\Exceptions\EntityException;

class UnitEnumColumn extends AbstractColumn
{
    /**
     * @throws EntityException
     */
    public function cast(mixed $dbValue): \UnitEnum
    {
        /** @var class-string<\UnitEnum> $enumClass */
        $enumClass = $this->type;
        if ($dbValue instanceof $enumClass) {
            return $dbValue;
        }
        $dbValue = strval($dbValue);
        foreach ($enumClass::cases() as $enum) {
            if ($enum->name === $dbValue) {
                return $enum;
            }
        }
        throw new EntityException("Case `$dbValue` not found in Enum `{$this->type}`");
    }

    /**
     * @param \UnitEnum $entityValue
     */
    public function uncast(mixed $entityValue): string
    {
        return $entityValue->name;
    }
}