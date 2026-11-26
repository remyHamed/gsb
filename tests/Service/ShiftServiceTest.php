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

    private function shiftWithId(
        string $id = self::SHIFT_ID,
        string $date = '2026-11-23',
        string $start = '11:30',
        string $end = '15:00',
        ?Week $week = null,
    ): Shift {
        $shift = (new Shift())
            ->setDate(new \DateTimeImmutable($date))
            ->setStartTime($start)
            ->setEndTime($end)
            ->setSkillsRequired(['caisse']);

        if ($week !== null) {
            $shift->setWeek($week);
        }

        $ref = new \ReflectionProperty(Shift::class, 'id');
        $ref->setValue($shift, Uuid::fromString($id));

        return $shift;
    }

    private function weekWithId(string $id = self::WEEK_ID): Week
    {
        $week = $this->week();
        $ref = new \ReflectionProperty(Week::class, 'id');
        $ref->setValue($week, Uuid::fromString($id));

        return $week;
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

    public function testCreateRejectsMalformedWeekId(): void
    {
        $this->mockweekRepository->expects(self::never())->method('findById');
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(WeekNotFoundException::class);

        $this->mockShiftService->create(new ShiftInput('not-a-uuid', '2026-11-23', '11:30', '15:00'));
    }

    public function testCreateThrowsWhenWeekDoesNotExist(): void
    {
        $this->mockweekRepository->method('findById')->willReturn(null);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(WeekNotFoundException::class);

        $this->mockShiftService->create(new ShiftInput(self::WEEK_ID, '2026-11-23', '11:30', '15:00'));
    }

    public function testCreateRejectsDateBeforeWeekStart(): void
    {
        $week = $this->weekWithId(); // 2026-11-23 au 2026-11-29
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->create(new ShiftInput(self::WEEK_ID, '2026-11-22', '11:30', '15:00'));
    }

    public function testCreateRejectsDateAfterWeekEnd(): void
    {
        $week = $this->weekWithId(); // 2026-11-23 au 2026-11-29
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->create(new ShiftInput(self::WEEK_ID, '2026-11-30', '11:30', '15:00'));
    }

    public function testCreateRejectsEndTimeBeforeOrEqualToStartTime(): void
    {
        $week = $this->weekWithId();
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->create(new ShiftInput(self::WEEK_ID, '2026-11-23', '15:00', '11:30'));
    }

    public function testCreateRejectsOverlappingShiftOnSameDay(): void
    {
        $week = $this->weekWithId();
        $existing = $this->shiftWithId('018f0000-0000-7000-8000-0000000000d2', '2026-11-23', '11:30', '15:00', $week);

        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->method('findByWeek')->willReturn([$existing]);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->create(new ShiftInput(self::WEEK_ID, '2026-11-23', '14:00', '18:00'));
    }

    public function testCreateRejectsWhenDailyQuotaOfTwoIsReached(): void
    {
        $week = $this->weekWithId();
        $shift1 = $this->shiftWithId('018f0000-0000-7000-8000-0000000000d2', '2026-11-23', '09:00', '11:30', $week);
        $shift2 = $this->shiftWithId('018f0000-0000-7000-8000-0000000000d3', '2026-11-23', '12:00', '15:00', $week);

        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->method('findByWeek')->willReturn([$shift1, $shift2]);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->create(new ShiftInput(self::WEEK_ID, '2026-11-23', '18:00', '22:00'));
    }

    public function testCreateRejectsWhenWeeklyQuotaOfFourteenIsReached(): void
    {
        $week = $this->weekWithId();
        $fourteenShifts = [];
        for ($i = 0; $i < 14; $i++) {
            $dayOffset = intdiv($i, 2);
            $date = (new \DateTimeImmutable('2026-11-23'))->modify("+{$dayOffset} days")->format('Y-m-d');
            $start = $i % 2 === 0 ? '11:00' : '18:00';
            $end = $i % 2 === 0 ? '15:00' : '22:00';
            $id = sprintf('018f0000-0000-7000-8000-0000000000%02x', $i + 1);
            $fourteenShifts[] = $this->shiftWithId($id, $date, $start, $end, $week);
        }

        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->method('findByWeek')->willReturn($fourteenShifts);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->create(new ShiftInput(self::WEEK_ID, '2026-11-23', '08:00', '10:30'));
    }

    public function testUpdatePersistsAndReturnsUpdatedShift(): void
    {
        $week = $this->weekWithId();
        $existingShift = $this->shiftWithId(self::SHIFT_ID, '2026-11-23', '11:30', '15:00', $week);

        $this->mockShiftsRepository->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::SHIFT_ID))
            ->willReturn($existingShift);
        $this->mockweekRepository->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::WEEK_ID))
            ->willReturn($week);
        $this->mockShiftsRepository->expects(self::once())->method('findByWeek')
            ->with(Uuid::fromString(self::WEEK_ID))
            ->willReturn([$existingShift]);

        $this->mockShiftsRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(function (Shift $shift) use ($week): bool {
                self::assertSame('2026-11-24', $shift->getDate()->format('Y-m-d'));
                self::assertSame('12:00', $shift->getStartTime());
                self::assertSame('15:30', $shift->getEndTime());
                self::assertSame(['salle', 'bar'], $shift->getSkillsRequired());
                self::assertSame($week, $shift->getWeek());

                return true;
            }));

        $input = new ShiftInput(self::WEEK_ID, '2026-11-24', '12:00', '15:30', ['salle', 'bar']);
        $result = $this->mockShiftService->update(self::SHIFT_ID, $input);

        self::assertSame($existingShift, $result);
    }

    public function testUpdateAllowsModifyingSkillsAndAdjacentSlotWithoutSelfConflict(): void
    {
        $week = $this->weekWithId();
        $morningShift = $this->shiftWithId('018f0000-0000-7000-8000-0000000000d2', '2026-11-23', '11:30', '15:00', $week);
        $targetShift = $this->shiftWithId(self::SHIFT_ID, '2026-11-24', '18:00', '22:00', $week);

        $this->mockShiftsRepository->method('findById')->willReturn($targetShift);
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->method('findByWeek')->willReturn([$morningShift, $targetShift]);
        $this->mockShiftsRepository->expects(self::once())->method('save');

        // Déplacé le 23/11 juste après le midi (15:00-22:00) avec changement libre de skills
        $input = new ShiftInput(self::WEEK_ID, '2026-11-23', '15:00', '22:00', ['bar', 'plonge']);
        $this->mockShiftService->update(self::SHIFT_ID, $input);
    }

    public function testUpdateRejectsMalformedShiftId(): void
    {
        $this->mockShiftsRepository->expects(self::never())->method('findById');
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(ShiftNotFoundException::class);

        $this->mockShiftService->update('not-a-uuid', new ShiftInput(self::WEEK_ID, '2026-11-23', '11:30', '15:00'));
    }

    public function testUpdateThrowsWhenShiftDoesNotExist(): void
    {
        $this->mockShiftsRepository->method('findById')->willReturn(null);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(ShiftNotFoundException::class);

        $this->mockShiftService->update(self::SHIFT_ID, new ShiftInput(self::WEEK_ID, '2026-11-23', '11:30', '15:00'));
    }

    public function testUpdateRejectsChangingWeek(): void
    {
        $otherWeekId = '018f0000-0000-7000-8000-0000000000c2';
        $week = $this->weekWithId(self::WEEK_ID);
        $otherWeek = $this->weekWithId($otherWeekId);
        $shift = $this->shiftWithId(self::SHIFT_ID, '2026-11-23', '11:30', '15:00', $week);

        $this->mockShiftsRepository->method('findById')->willReturn($shift);
        $this->mockweekRepository->method('findById')
            ->willReturnCallback(static fn (Uuid $id): ?Week => match ($id->toRfc4122()) {
                self::WEEK_ID => $week,
                $otherWeekId => $otherWeek,
                default => null,
            });
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->update(self::SHIFT_ID, new ShiftInput($otherWeekId, '2026-11-23', '11:30', '15:00'));
    }

    public function testUpdateRejectsDateBeforeWeekStart(): void
    {
        $week = $this->weekWithId(); // 2026-11-23 au 2026-11-29
        $shift = $this->shiftWithId(self::SHIFT_ID, '2026-11-23', '11:30', '15:00', $week);

        $this->mockShiftsRepository->method('findById')->willReturn($shift);
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->update(self::SHIFT_ID, new ShiftInput(self::WEEK_ID, '2026-11-22', '11:30', '15:00'));
    }

    public function testUpdateRejectsDateAfterWeekEnd(): void
    {
        $week = $this->weekWithId(); // 2026-11-23 au 2026-11-29
        $shift = $this->shiftWithId(self::SHIFT_ID, '2026-11-23', '11:30', '15:00', $week);

        $this->mockShiftsRepository->method('findById')->willReturn($shift);
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->update(self::SHIFT_ID, new ShiftInput(self::WEEK_ID, '2026-11-30', '11:30', '15:00'));
    }

    public function testUpdateRejectsEndTimeBeforeOrEqualToStartTime(): void
    {
        $week = $this->weekWithId();
        $shift = $this->shiftWithId(self::SHIFT_ID, '2026-11-23', '11:30', '15:00', $week);

        $this->mockShiftsRepository->method('findById')->willReturn($shift);
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->update(self::SHIFT_ID, new ShiftInput(self::WEEK_ID, '2026-11-23', '15:00', '11:30'));
    }

    public function testUpdateRejectsOverlappingShiftOnSameDay(): void
    {
        $week = $this->weekWithId();
        $morningShift = $this->shiftWithId('018f0000-0000-7000-8000-0000000000d2', '2026-11-23', '11:30', '15:00', $week);
        $eveningShift = $this->shiftWithId(self::SHIFT_ID, '2026-11-23', '18:00', '22:00', $week);

        $this->mockShiftsRepository->method('findById')->willReturn($eveningShift);
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->method('findByWeek')->willReturn([$morningShift, $eveningShift]);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->update(self::SHIFT_ID, new ShiftInput(self::WEEK_ID, '2026-11-23', '14:00', '18:00'));
    }

    public function testUpdateRejectsMovingToDayWithTwoShiftsAlready(): void
    {
        $week = $this->weekWithId();
        $mondayShift1 = $this->shiftWithId('018f0000-0000-7000-8000-0000000000d2', '2026-11-23', '09:00', '11:30', $week);
        $mondayShift2 = $this->shiftWithId('018f0000-0000-7000-8000-0000000000d3', '2026-11-23', '12:00', '15:00', $week);
        $tuesdayShift = $this->shiftWithId(self::SHIFT_ID, '2026-11-24', '18:00', '22:00', $week);

        $this->mockShiftsRepository->method('findById')->willReturn($tuesdayShift);
        $this->mockweekRepository->method('findById')->willReturn($week);
        $this->mockShiftsRepository->method('findByWeek')->willReturn([$mondayShift1, $mondayShift2, $tuesdayShift]);
        $this->mockShiftsRepository->expects(self::never())->method('save');

        $this->expectException(\DomainException::class);

        $this->mockShiftService->update(self::SHIFT_ID, new ShiftInput(self::WEEK_ID, '2026-11-23', '18:00', '22:00'));
    }
    
    public function testDeleteRemovesExistingShift(): void
    {
        $shift = $this->shiftWithId();

        $this->mockShiftsRepository->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::SHIFT_ID))
            ->willReturn($shift);

        $this->mockShiftsRepository->expects(self::once())
            ->method('remove')
            ->with($shift);

        $this->mockShiftService->delete(self::SHIFT_ID);
    }

    public function testDeleteThrowsWhenShiftDoesNotExist(): void
    {
        $this->mockShiftsRepository->method('findById')->willReturn(null);
        $this->mockShiftsRepository->expects(self::never())->method('remove');

        $this->expectException(ShiftNotFoundException::class);

        $this->mockShiftService->delete(self::SHIFT_ID);
    }

    public function testDeleteRejectsMalformedId(): void
    {
        $this->mockShiftsRepository->expects(self::never())->method('findById');
        $this->mockShiftsRepository->expects(self::never())->method('remove');

        $this->expectException(ShiftNotFoundException::class);

        $this->mockShiftService->delete('not-a-uuid');
    }
}