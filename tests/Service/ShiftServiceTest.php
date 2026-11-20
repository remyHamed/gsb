<?php

namespace App\Tests\Service;

use App\Entity\Shift;
use App\Exception\WeekNotFoundException;
use App\Entity\Week;
use App\Repository\Contract\ShiftRepositoryInterface;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Impl\ShiftService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class ShiftServiceTest extends TestCase
{
    private const WEEK_ID = '018f0000-0000-7000-8000-0000000000c1';

    private ShiftRepositoryInterface|MockObject $shifts;
    private WeekRepositoryInterface|MockObject $weeks;
    private ShiftService $service;

    protected function setUp(): void
    {
        $this->shifts = $this->createMock(ShiftRepositoryInterface::class);
        $this->weeks = $this->createMock(WeekRepositoryInterface::class);
        $this->service = new ShiftService($this->shifts, $this->weeks);
    }

    private function shift(): Shift
    {
        return (new Shift())
            ->setDate(new \DateTimeImmutable('2026-11-23'))
            ->setStartTime('11:30')
            ->setEndTime('15:00')
            ->setSkillsRequired(['caisse']);
    }

    public function testFindAllReturnsRepositoryResult(): void
    {
        $expected = [$this->shift(), $this->shift()];
        $this->shifts->expects(self::once())->method('findAll')->willReturn($expected);

        self::assertSame($expected, $this->service->findAll());
    }

    public function testFindByWeekReturnsShiftsOfThatWeek(): void
    {
        $expected = [$this->shift()];

        $this->weeks->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::WEEK_ID))
            ->willReturn(new Week());
        $this->shifts->expects(self::once())
            ->method('findByWeek')
            ->with(Uuid::fromString(self::WEEK_ID))
            ->willReturn($expected);

        self::assertSame($expected, $this->service->findByWeek(self::WEEK_ID));
    }

    public function testFindByWeekRejectsMalformedWeekId(): void
    {
        $this->weeks->expects(self::never())->method('findById');
        $this->shifts->expects(self::never())->method('findByWeek');

        $this->expectException(WeekNotFoundException::class);

        $this->service->findByWeek('not-a-uuid');
    }

    public function testFindByWeekThrowsWhenWeekDoesNotExist(): void
    {
        $this->weeks->method('findById')->willReturn(null);
        $this->shifts->expects(self::never())->method('findByWeek');

        $this->expectException(WeekNotFoundException::class);

        $this->service->findByWeek(self::WEEK_ID);
    }
}