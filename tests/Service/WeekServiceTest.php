<?php

namespace App\Tests\Service;

use App\Dto\WeekInput;
use App\Entity\Week;
use App\Exception\WeekAlreadyExistsException;
use App\Exception\WeekNotFoundException;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Impl\WeekService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use App\Exception\InvalidWeekPeriodException;
use App\Exception\WeekOverlapException;

final class WeekServiceTest extends TestCase
{
    private const WEEK_ID = '018f0000-0000-7000-8000-000000000001';

    private WeekRepositoryInterface|MockObject $weeks;
    private WeekService $service;

    protected function setUp(): void
    {
        $this->weeks = $this->createMock(WeekRepositoryInterface::class);
        $this->service = new WeekService($this->weeks);
    }

    private function input(array $overrides = []): WeekInput
    {
        return new WeekInput(...array_merge([
            'year' => 2026,
            'weekNumber' => 48,
            'startDate' => '2026-11-23',
        ], $overrides));
    }

    private function week(): Week
    {
        $week = new Week();
        $week->setYear(2026);
        $week->setWeekNumber(48);
        $week->setStartDate(new \DateTimeImmutable('2026-11-23'));

        return $week;
    }

    public function testFindAllReturnsRepositoryResult(): void
    {
        $expected = [$this->week()];
        $this->weeks->expects(self::once())->method('findAll')->willReturn($expected);

        self::assertSame($expected, $this->service->findAll());
    }

    public function testFindByIdReturnsWeek(): void
    {
        $week = $this->week();
        $this->weeks->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::WEEK_ID))
            ->willReturn($week);

        self::assertSame($week, $this->service->findById(self::WEEK_ID));
    }

    public function testFindByIdRejectsMalformedId(): void
    {
        $this->weeks->expects(self::never())->method('findById');

        $this->expectException(WeekNotFoundException::class);

        $this->service->findById('not-a-uuid');
    }

    public function testCreatePersistsWeek(): void
    {
        $this->weeks->expects(self::once())->method('existsDuplicate')->with(2026, 48, null)->willReturn(false);
        $this->weeks->expects(self::once())
            ->method('overlapsPeriod')
            ->with(
                self::equalTo(new \DateTimeImmutable('2026-11-23')),
                self::equalTo(new \DateTimeImmutable('2026-11-30')),
                null
            )
            ->willReturn(false);
        $this->weeks->expects(self::once())->method('save')->with(self::callback(
            static fn (Week $week): bool => $week->getYear() === 2026
                && $week->getWeekNumber() === 48
                && $week->getStartDate()?->format('Y-m-d') === '2026-11-23'
        ));

        $week = $this->service->create($this->input());

        self::assertSame(48, $week->getWeekNumber());
    }

    public function testCreateRejectsInvalidDateFormat(): void
    {
        $this->weeks->expects(self::never())->method('existsDuplicate');
        $this->weeks->expects(self::never())->method('overlapsPeriod');
        $this->weeks->expects(self::never())->method('save');

        $this->expectException(InvalidWeekPeriodException::class);

        $this->service->create($this->input(['startDate' => 'not-a-date']));
    }

    public function testCreateRejectsInvalidWeekNumber(): void
    {
        $this->weeks->expects(self::never())->method('existsDuplicate');
        $this->weeks->expects(self::never())->method('save');

        $this->expectException(InvalidWeekPeriodException::class);

        // 2027 n'a que 52 semaines ISO : la "semaine 53" tombe le 2028-01-03
        $this->service->create($this->input([
            'year' => 2027,
            'weekNumber' => 53,
            'startDate' => '2028-01-03',
        ]));
    }

    public function testCreateRejectsStartDateNotMatchingIsoWeek(): void
    {
        $this->weeks->expects(self::never())->method('existsDuplicate');
        $this->weeks->expects(self::never())->method('save');

        $this->expectException(InvalidWeekPeriodException::class);

        // 2026-11-24 est un mardi, pas le lundi de la semaine 48 (2026-11-23)
        $this->service->create($this->input([
            'year' => 2026,
            'weekNumber' => 48,
            'startDate' => '2026-11-24',
        ]));
    }

    public function testCreateRejectsOverlappingPeriod(): void
    {
        $this->weeks->expects(self::once())->method('existsDuplicate')->with(2026, 48, null)->willReturn(false);
        $this->weeks->expects(self::once())
            ->method('overlapsPeriod')
            ->with(
                self::equalTo(new \DateTimeImmutable('2026-11-23')),
                self::equalTo(new \DateTimeImmutable('2026-11-30')),
                null
            )
            ->willReturn(true);
        $this->weeks->expects(self::never())->method('save');

        $this->expectException(WeekOverlapException::class);

        $this->service->create($this->input());
    }

    public function testCreateRejectsDuplicate(): void
    {
        $this->weeks->expects(self::once())->method('existsDuplicate')->with(2026, 48, null)->willReturn(true);
        $this->weeks->expects(self::never())->method('save');

        $this->expectException(WeekAlreadyExistsException::class);

        $this->service->create($this->input());
    }

    public function testUpdateChangesFields(): void
    {
        $week = $this->week();
        (new \ReflectionProperty(Week::class, 'id'))
            ->setValue($week, Uuid::fromString(self::WEEK_ID));

        $this->weeks->method('findById')->willReturn($week);
        $this->weeks->expects(self::once())->method('existsDuplicate')
            ->with(2027, 2, $week->getId())
            ->willReturn(false);
        $this->weeks->expects(self::once())->method('save')->with(self::identicalTo($week));

        $updated = $this->service->update(self::WEEK_ID, $this->input([
            'year' => 2027,
            'weekNumber' => 2,
            'startDate' => '2027-01-11',
        ]));

        self::assertSame(2027, $updated->getYear());
        self::assertSame(2, $updated->getWeekNumber());
    }

    public function testUpdateRejectsInvalidPeriod(): void
    {
        $week = $this->week();
        $this->weeks->method('findById')->willReturn($week);
        $this->weeks->expects(self::never())->method('save');

        $this->expectException(InvalidWeekPeriodException::class);

        $this->service->update(self::WEEK_ID, $this->input([
            'year' => 2027,
            'weekNumber' => 2,
            'startDate' => '2027-01-12', // mardi au lieu de lundi 2027-01-11
        ]));
    }

    public function testUpdateRejectsOverlappingPeriod(): void
    {
        $week = $this->week();
        (new \ReflectionProperty(Week::class, 'id'))
            ->setValue($week, Uuid::fromString(self::WEEK_ID));

        $this->weeks->method('findById')->willReturn($week);
        $this->weeks->expects(self::once())
            ->method('existsDuplicate')
            ->with(2027, 2, $week->getId())
            ->willReturn(false);
        $this->weeks->expects(self::once())
            ->method('overlapsPeriod')
            ->with(
                self::equalTo(new \DateTimeImmutable('2027-01-11')),
                self::equalTo(new \DateTimeImmutable('2027-01-18')),
                $week->getId()
            )
            ->willReturn(true);
        $this->weeks->expects(self::never())->method('save');

        $this->expectException(WeekOverlapException::class);

        $this->service->update(self::WEEK_ID, $this->input([
            'year' => 2027,
            'weekNumber' => 2,
            'startDate' => '2027-01-11',
        ]));
    }

    public function testDeleteRemovesWeek(): void
    {
        $week = $this->week();
        $this->weeks->method('findById')->willReturn($week);
        $this->weeks->expects(self::once())->method('remove')->with(self::identicalTo($week));

        $this->service->delete(self::WEEK_ID);
    }
}
