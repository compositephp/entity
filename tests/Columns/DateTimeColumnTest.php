<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Helpers\DateTimeHelper;

final class DateTimeColumnTest extends \PHPUnit\Framework\TestCase
{
    public static function cast_dataProvider(): array
    {
        $now = new \DateTime();
        $nowImmutable = new \DateTimeImmutable(DateTimeHelper::dateTimeToString($now));
        return [
            [
                'value' => null,
                'dateTime' => null,
                'dateTimeImmutable' => null,
            ],
            [
                'value' => '',
                'dateTime' => null,
                'dateTimeImmutable' => null,
            ],
            [
                'value' => 'abc',
                'dateTime' => null,
                'dateTimeImmutable' => null,
            ],
            [
                'value' => DateTimeHelper::DEFAULT_TIMESTAMP,
                'dateTime' => null,
                'dateTimeImmutable' => null,
            ],
            [
                'value' => DateTimeHelper::DEFAULT_TIMESTAMP_MICRO,
                'dateTime' => null,
                'dateTimeImmutable' => null,
            ],
            [
                'value' => DateTimeHelper::DEFAULT_DATETIME,
                'dateTime' => null,
                'dateTimeImmutable' => null,
            ],
            [
                'value' => DateTimeHelper::DEFAULT_DATETIME_MICRO,
                'dateTime' => null,
                'dateTimeImmutable' => null,
            ],
            [
                'value' => DateTimeHelper::dateTimeToString($now),
                'dateTime' => $now,
                'dateTimeImmutable' => $nowImmutable,
            ],
            [
                'value' => '2000-01-01 00:00:00',
                'dateTime' => new \DateTime('2000-01-01 00:00:00'),
                'dateTimeImmutable' => new \DateTimeImmutable('2000-01-01 00:00:00'),
            ],
            [
                'value' => new \DateTime('2000-01-01 00:00:00'),
                'dateTime' => new \DateTime('2000-01-01 00:00:00'),
                'dateTimeImmutable' => null,
            ],
            [
                'value' => new \DateTimeImmutable('2000-01-01 00:00:00'),
                'dateTime' => null,
                'dateTimeImmutable' => new \DateTimeImmutable('2000-01-01 00:00:00'),
            ],
        ];
    }

    /**
     * @dataProvider cast_dataProvider
     */
    public function test_cast(mixed $value, ?\DateTime $dateTime, ?\DateTimeImmutable $dateTimeImmutable): void
    {
        $class = new class extends AbstractEntity {
            public function __construct(
                public ?\DateTime $date_time = null,
                public ?\DateTimeImmutable $date_time_immutable = null,
            ) {}
        };
        $entity = $class::fromArray(['date_time' => $value, 'date_time_immutable' => $value]);
        $this->assertEquals($dateTime, $entity->date_time);
        $this->assertEquals($dateTimeImmutable, $entity->date_time_immutable);
    }

    public static function uncast_dataProvider(): array
    {
        $dateTime = new \DateTimeImmutable();
        return [
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => $dateTime,
                'expected' => DateTimeHelper::dateTimeToString($dateTime),
            ],
        ];
    }

    /**
     * @dataProvider uncast_dataProvider
     */
    public function test_uncast(mixed $value, mixed $expected): void
    {
        $entity = new class($value) extends AbstractEntity {
            public function __construct(
                public ?\DateTimeImmutable $column,
            ) {}
        };
        $actual = $entity->toArray()['column'];
        $this->assertSame($expected, $actual);

        $newEntity = $entity::fromArray(['column' => $actual]);
        $newActual = $newEntity->toArray()['column'];
        $this->assertSame($entity->column?->format('Uu'), $newEntity->column?->format('Uu'));
        $this->assertSame($expected, $newActual);
    }

    public function test_default(): void
    {
        $entity = new class() extends AbstractEntity {
            public function __construct(
                public ?\DateTimeImmutable $column = null,
            ) {}
        };
        $entity->column = DateTimeHelper::getDefaultDateTimeImmutable();
        $this->assertNull($entity->toArray()['column']);

        $entity->column = null;
        $this->assertNull($entity->toArray()['column']);

        $newEntity = $entity::fromArray(['column' => DateTimeHelper::DEFAULT_DATETIME_MICRO]);
        $this->assertNull($newEntity->column);

        $newEntity = $entity::fromArray(['column' => null]);
        $this->assertNull($newEntity->column);
    }
}