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
use App\Exception\ShiftNotFoundException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class ShiftServiceTest extends TestCase
{
    private const WEEK_ID = '018f0000-0000-7000-8000-0000000000c1';
    private const SHIFT_ID = '018f0000-0000-7000-8000-0000000000d1';

    private ShiftRepositoryInterface|MockObject $mockShiftsRepository;
    private WeekRepositoryInterface|MockObject $mockweekRepository;
    private ShiftService $service;

    protected function setUp(): void
    {
        $this->mockShiftsRepository = $this->createMock(ShiftRepositoryInterface::class);
        $this->mockweekRepository = $this->createMock(WeekRepositoryInterface::class);
        $this->service = new ShiftService($this->mockShiftsRepository, $this->mockweekRepository);
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
        $this->mockShiftsRepository->expects(self::once())->method('findAll')->willReturn($expected);

        self::assertSame($expected, $this->service->findAll());
    }

    public function testFindByWeekReturnsShiftsOfThatWeek(): void
    {
        $expected = [$this->shift()];

        $this->mockweekRepository->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::WEEK_ID))
            ->willReturn(new Week());
        $this->mockShiftsRepository->expects(self::once())
            ->method('findByWeek')
            ->with(Uuid::fromString(self::WEEK_ID))
            ->willReturn($expected);

        self::assertSame($expected, $this->service->findByWeek(self::WEEK_ID));
    }

    public function testFindByWeekRejectsMalformedWeekId(): void
    {
        $this->mockweekRepository->expects(self::never())->method('findById');
        $this->mockShiftsRepository->expects(self::never())->method('findByWeek');

        $this->expectException(WeekNotFoundException::class);

        $this->service->findByWeek('not-a-uuid');
    }

    public function testFindByWeekThrowsWhenWeekDoesNotExist(): void
    {
        $this->mockweekRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mockShiftsRepository->expects(self::never())->method('findByWeek');

        $this->expectException(WeekNotFoundException::class);

        $this->service->findByWeek(self::WEEK_ID);
    }

    public function testFindByIdReturnsShift(): void
    {
        $shift = $this->shift();
        $this->mockShiftsRepository->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::SHIFT_ID))
            ->willReturn($shift);

        self::assertSame($shift, $this->service->findById(self::SHIFT_ID));
    }

    public function testFindByIdThrowsWhenShiftDoesNotExist(): void
    {
        $this->mockShiftsRepository->method('findById')->willReturn(null);

        $this->expectException(ShiftNotFoundException::class);

        $this->service->findById(self::SHIFT_ID);
    }

    public function testFindByIdRejectsMalformedId(): void
    {
        $this->mockShiftsRepository->expects(self::never())->method('findById');

        $this->expectException(ShiftNotFoundException::class);

        $this->service->findById('not-a-uuid');
    }
}