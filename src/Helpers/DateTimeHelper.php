<?php declare(strict_types=1);

namespace Composite\Entity\Helpers;

class DateTimeHelper
{
    final public const DEFAULT_TIMESTAMP = '1970-01-01 00:00:01';
    final public const DEFAULT_TIMESTAMP_MICRO = '1970-01-01 00:00:01.000000';
    final public const DEFAULT_DATETIME = '1000-01-01 00:00:00';
    final public const DEFAULT_DATETIME_MICRO = '1000-01-01 00:00:00.000000';
    final public const DATETIME_FORMAT = 'Y-m-d H:i:s';
    final public const DATETIME_MICRO_FORMAT = 'Y-m-d H:i:s.u';

    public static function getDefaultDateTimeImmutable() : \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::DEFAULT_TIMESTAMP);
    }

    public static function dateTimeToString(\DateTimeInterface $dateTime, bool $withMicro = true): string
    {
        return $dateTime->format($withMicro ? self::DATETIME_MICRO_FORMAT : self::DATETIME_FORMAT);
    }

    public static function isDefault(mixed $value): bool
    {
        if (!$value) {
            return true;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp() <= 1;
        }
        return $value === self::DEFAULT_TIMESTAMP
            || $value === self::DEFAULT_TIMESTAMP_MICRO
            || $value === self::DEFAULT_DATETIME
            || $value === self::DEFAULT_DATETIME_MICRO;
    }
}
