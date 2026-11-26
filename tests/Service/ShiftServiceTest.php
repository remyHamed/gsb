<?php

namespace App\Tests\Service;

use App\Entity\Shift;
use App\Dto\Shift\ShiftInput;
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
    private ShiftService $mockShiftService;

    protected function setUp(): void
    {
        $this->mockShiftsRepository = $this->createMock(ShiftRepositoryInterface::class);
        $this->mockweekRepository = $this->createMock(WeekRepositoryInterface::class);
        $this->mockShiftService = new ShiftService($this->mockShiftsRepository, $this->mockweekRepository);
    }

    private function shift(): Shift
    {
        return (new Shift())
            ->setDate(new \DateTimeImmutable('2026-11-23'))
            ->setStartTime('11:30')
            ->setEndTime('15:00')
            ->setSkillsRequired(['caisse']);
    }

    private function week(): Week
    {
        return (new Week())
            ->setYear(2026)
            ->setWeekNumber(48)
            ->setStartDate(new \DateTimeImmutable('2026-11-23'));
    }

    public function testFindAllReturnsRepositoryResult(): void
    {
        $expected = [$this->shift(), $this->shift()];
        $this->mockShiftsRepository->expects(self::once())->method('findAll')->willReturn($expected);

        self::assertSame($expected, $this->mockShiftService->findAll());
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

        self::assertSame($expected, $this->mockShiftService->findByWeek(self::WEEK_ID));
    }

    public function testFindByWeekRejectsMalformedWeekId(): void
    {
        $this->mockweekRepository->expects(self::never())->method('findById');
        $this->mockShiftsRepository->expects(self::never())->method('findByWeek');

        $this->expectException(WeekNotFoundException::class);

        $this->mockShiftService->findByWeek('not-a-uuid');
    }

    public function testFindByWeekThrowsWhenWeekDoesNotExist(): void
    {
        $this->mockweekRepository->expects(self::once())->method('findById')->willReturn(null);
        $this->mockShiftsRepository->expects(self::never())->method('findByWeek');

        $this->expectException(WeekNotFoundException::class);

        $this->mockShiftService->findByWeek(self::WEEK_ID);
    }

    public function testFindByIdReturnsShift(): void
    {
        $shift = $this->shift();
        $this->mockShiftsRepository->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::SHIFT_ID))
            ->willReturn($shift);

        self::assertSame($shift, $this->mockShiftService->findById(self::SHIFT_ID));
    }

    public function testFindByIdThrowsWhenShiftDoesNotExist(): void
    {
        $this->mockShiftsRepository->method('findById')->willReturn(null);

        $this->expectException(ShiftNotFoundException::class);

        $this->mockShiftService->findById(self::SHIFT_ID);
    }

    public function testFindByIdRejectsMalformedId(): void
    {
        $this->mockShiftsRepository->expects(self::never())->method('findById');

        $this->expectException(ShiftNotFoundException::class);

        $this->mockShiftService->findById('not-a-uuid');
    }

    public function testCreatePersistsAndReturnsShift():void
    {
        $week = $this->week();
        $date = new \DateTimeImmutable('2026-11-23');

        $this->mockweekRepository->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::WEEK_ID))
            ->willReturn($week);

        $this->mockShiftsRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Shift $shift) use ($week, $date) {
                self::assertEquals($date, $shift->getDate());
                self::assertSame('11:30', $shift->getStartTime());
                self::assertSame('15:00', $shift->getEndTime());
                self::assertSame(['caisse'], $shift->getSkillsRequired());
                self::assertSame($week, $shift->getWeek());

                return true;
            }))
            ->willReturnCallback(fn (Shift $shift) => $shift);
            
            $shiftInput = new ShiftInput(self::WEEK_ID, '2026-11-23', '11:30', '15:00', ['caisse']);

        $result = $this->mockShiftService->create($shiftInput);

        self::assertInstanceOf(Shift::class, $result);
        self::assertSame($week, $result->getWeek());
    }
    
   /* public function testCreateRejectsMalformedWeekId():void
    {
        
        
    }

    public function testCreateThrowsWhenWeekDoesNotExist():void
    {
        
    }

    public function testCreateRejectsDateBeforeWeekStart():void
    {
        
    }

    public function testCreateRejectsDateAfterWeekEnd():void
    {
        
    }
    public function testCreateRejectsEndTimeBeforeOrEqualToStartTime():void
    {
        
    }
    public function testCreateRejectsWhenDailyQuotaOfTwoIsReached():void
    {
        
    }
    public function testCreateRejectsWhenWeeklyQuotaOfFourteenIsReached():void
    {
        
    }*/
}