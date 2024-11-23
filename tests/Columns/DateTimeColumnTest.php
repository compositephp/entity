<?php declare(strict_types=1);

namespace Composite\Entity\Tests\Columns;

use Composite\Entity\AbstractEntity;
use Composite\Entity\Attributes\Date;
use Composite\Entity\Helpers\DateTimeHelper;
use DateTimeImmutable;

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


    public static function date_dataProvider(): array
    {
        return [
            [
                'value' => '2020-01-02 23:59:59',
                'expected' => '2020-01-02',
            ],
            [
                'value' => '2000-02-02 00:00:00',
                'expected' => '2000-02-02',
            ],
            [
                'value' => '1990-07-07',
                'expected' => '1990-07-07',
            ],
            [
                'value' => 'not valid',
                'expected' => null,
            ],
            [
                'value' => null,
                'expected' => null,
            ],
            [
                'value' => false,
                'expected' => null,
            ],
        ];
    }

    /**
     * @dataProvider date_dataProvider
     */
    public function test_dateUncast(mixed $value, ?string $expected): void
    {
        $class = new class() extends AbstractEntity {
            public function __construct(
                #[Date]
                public ?\DateTimeImmutable $column = null,
            ) {}
        };

        $entity = $class::fromArray(['column' => $value]);
        $actual = $entity->toArray()['column'];
        $this->assertEquals($expected, $actual);
    }

    public function test_dateCast(): void
    {
        $date = '2000-01-01';
        $dti = new \DateTimeImmutable($date . ' 12:00:00');
        $entity = new class($dti) extends AbstractEntity {
            public function __construct(
                #[Date]
                public \DateTimeImmutable $column,
            ) {}
        };
        $entity->resetChangedColumns();

        $actual = $entity->toArray()['column'];
        $this->assertEquals($date, $actual);
        $this->assertEmpty($entity->getChangedColumns());

        $entity->column = new DateTimeImmutable($date . ' 23:59:59');
        $this->assertEmpty($entity->getChangedColumns());

        $newDate = '2020-07-07';
        $entity->column = new DateTimeImmutable($newDate . ' 07:07:07');
        $this->assertEquals(
            ['column' => $newDate],
            $entity->getChangedColumns(),
        );
    }
}