<?php declare(strict_types=1);

namespace Composite\Entity\Columns;

use Composite\Entity\Attributes\Date;
use Composite\Entity\Helpers\DateTimeHelper;

class DateTimeColumn extends AbstractColumn
{
    /**
     * @throws \Exception
     */
    public function cast(mixed $dbValue): ?\DateTimeInterface
    {
        if (DateTimeHelper::isDefault($dbValue)) {
            return null;
        }
        /** @var class-string<\DateTimeInterface> $class */
        $class = $this->type;
        if (is_string($dbValue)) {
            $timeValue = $this->isDate() ? substr($dbValue, 0, 10) : $dbValue;
            return new $class($timeValue);
        } elseif ($dbValue instanceof $class) {
            return $dbValue;
        } else {
            return null;
        }
    }

    /**
     * @param \DateTimeInterface $entityValue
     */
    public function uncast(mixed $entityValue): ?string
    {
        if ($this->isDate()) {
            return $entityValue->format(DateTimeHelper::DATE_FORMAT);
        }
        if ($this->isNullable && DateTimeHelper::isDefault($entityValue)) {
            return null;
        }
        return DateTimeHelper::dateTimeToString($entityValue);
    }

    private function isDate(): bool
    {
        return (bool)$this->getFirstAttributeByClass(Date::class);
    }
}