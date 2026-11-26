<?php

namespace App\Tests\Scheduling;

use App\Entity\Account;
use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;
use App\Exception\EmployeeNotFoundException;
use App\Exception\ShiftNotFoundException;
use App\Exception\WeekNotFoundException;
use App\Repository\Contract\EmployeeRepositoryInterface;
use App\Repository\Contract\ShiftRepositoryInterface;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Impl\Scheduling\EligibilityEvaluator;
use App\Service\Impl\Scheduling\Rule\HasSkillRule;
use App\Service\Impl\Scheduling\Rule\IsActiveRule;
use App\Service\Impl\Scheduling\Rule\IsAvailableRule;
use App\Service\Impl\Scheduling\Rule\NotAlreadyAssignedRule;
use App\Service\Impl\Scheduling\Rule\WeeklyHoursLimitRule;
use App\Service\Impl\Scheduling\ScheduleGenerator;
use App\Service\Impl\Scheduling\SchedulingService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SchedulingServiceTest extends TestCase
{
    private function createService(
        WeekRepositoryInterface $weeks,
        ShiftRepositoryInterface $shifts,
        EmployeeRepositoryInterface $employees,
    ): SchedulingService {
        $evaluator = new EligibilityEvaluator([
            new IsActiveRule(),
            new HasSkillRule(),
            new IsAvailableRule(),
            new NotAlreadyAssignedRule(),
            new WeeklyHoursLimitRule(),
        ]);

        return new SchedulingService(
            $weeks,
            $shifts,
            $employees,
            $evaluator,
            new ScheduleGenerator($evaluator),
        );
    }

    public function testGetEligibleEmployeesForShiftReturnsOnlyEligibleSortedBySeniority(): void
    {
        $shiftId = Uuid::v4();
        $week = new Week();
        $shift = $this->makeShift($week, '2026-11-23', '11:30', '15:00', ['salle']);

        $junior = $this->makeEmployee('junior@example.com', dateAnciennete: '2025-01-01', skills: ['salle']);
        $senior = $this->makeEmployee('senior@example.com', dateAnciennete: '2021-01-01', skills: ['salle']);
        $ineligible = $this->makeEmployee('cook@example.com', skills: ['cuisine']);

        $shiftRepo = $this->createStub(ShiftRepositoryInterface::class);
        $shiftRepo->method('findById')->willReturn($shift);

        $empRepo = $this->createStub(EmployeeRepositoryInterface::class);
        $empRepo->method('findAll')->willReturn([$junior, $senior, $ineligible]);

        $service = $this->createService(
            $this->createStub(WeekRepositoryInterface::class),
            $shiftRepo,
            $empRepo,
        );

        $eligible = $service->getEligibleEmployeesForShift($shiftId->toRfc4122());

        self::assertSame([$senior, $junior], $eligible);
    }

    public function testAssignEmployeeSucceedsWhenEligibleAndThrowsWhenIneligible(): void
    {
        $shiftId = Uuid::v4();
        $eligibleId = Uuid::v4();
        $ineligibleId = Uuid::v4();

        $week = new Week();
        $shift = $this->makeShift($week, '2026-11-23', '11:30', '15:00', ['salle']);

        $eligible = $this->makeEmployee('ok@example.com', skills: ['salle']);
        $ineligible = $this->makeEmployee('ko@example.com', skills: ['cuisine']);

        $shiftRepo = $this->createMock(ShiftRepositoryInterface::class);
        $shiftRepo->method('findById')->willReturn($shift);
        $shiftRepo->expects(self::once())->method('save')->with($shift);

        $empRepo = $this->createStub(EmployeeRepositoryInterface::class);
        $empRepo->method('findById')->willReturnCallback(
            static fn (Uuid $id): ?Employee => match ($id->toRfc4122()) {
                $eligibleId->toRfc4122() => $eligible,
                $ineligibleId->toRfc4122() => $ineligible,
                default => null,
            }
        );

        $service = $this->createService(
            $this->createStub(WeekRepositoryInterface::class),
            $shiftRepo,
            $empRepo,
        );

        $updated = $service->assignEmployee($shiftId->toRfc4122(), $eligibleId->toRfc4122());
        self::assertTrue($updated->getAssignedEmployees()->contains($eligible));

        $this->expectException(\DomainException::class);
        $service->assignEmployee($shiftId->toRfc4122(), $ineligibleId->toRfc4122());
    }

    public function testUnassignEmployeeRemovesEmployeeAndSaves(): void
    {
        $shiftId = Uuid::v4();
        $employeeId = Uuid::v4();

        $week = new Week();
        $shift = $this->makeShift($week, '2026-11-23', '11:30', '15:00');
        $employee = $this->makeEmployee('emp@example.com');
        $shift->addAssignedEmployee($employee);

        $shiftRepo = $this->createMock(ShiftRepositoryInterface::class);
        $shiftRepo->method('findById')->willReturn($shift);
        $shiftRepo->expects(self::once())->method('save')->with($shift);

        $empRepo = $this->createStub(EmployeeRepositoryInterface::class);
        $empRepo->method('findById')->willReturn($employee);

        $service = $this->createService(
            $this->createStub(WeekRepositoryInterface::class),
            $shiftRepo,
            $empRepo,
        );

        $updated = $service->unassignEmployee($shiftId->toRfc4122(), $employeeId->toRfc4122());
        self::assertFalse($updated->getAssignedEmployees()->contains($employee));
    }

    public function testGenerateWeekSchedulePopulatesShiftsAndSaves(): void
    {
        $weekId = Uuid::v4();
        $week = new Week();
        $shift = $this->makeShift($week, '2026-11-23', '11:30', '15:00');
        $alice = $this->makeEmployee('alice@example.com');

        $weekRepo = $this->createStub(WeekRepositoryInterface::class);
        $weekRepo->method('findById')->willReturn($week);

        $shiftRepo = $this->createMock(ShiftRepositoryInterface::class);
        $shiftRepo->expects(self::once())->method('save')->with($shift);

        $empRepo = $this->createStub(EmployeeRepositoryInterface::class);
        $empRepo->method('findAll')->willReturn([$alice]);

        $service = $this->createService($weekRepo, $shiftRepo, $empRepo);

        $result = $service->generateWeekSchedule($weekId->toRfc4122());

        self::assertSame($week, $result);
        self::assertTrue($shift->getAssignedEmployees()->contains($alice));
    }

    public function testThrowsNotFoundExceptionsOnInvalidIds(): void
    {
        $service = $this->createService(
            $this->createStub(WeekRepositoryInterface::class),
            $this->createStub(ShiftRepositoryInterface::class),
            $this->createStub(EmployeeRepositoryInterface::class),
        );

        $this->expectException(ShiftNotFoundException::class);
        $service->getEligibleEmployeesForShift('invalid-uuid');
    }

    private function makeEmployee(
        string $email,
        int $weeklyHours = 35,
        array $availableSlots = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
        array $skills = [],
        string $dateAnciennete = '2024-01-01',
    ): Employee {
        $account = (new Account())->setEmail($email);

        return (new Employee(
            $account,
            'Test',
            'User',
            new \DateTimeImmutable($dateAnciennete),
            $weeklyHours,
        ))
            ->setAvailableSlots($availableSlots)
            ->setSkills($skills);
    }

    private function makeShift(
        Week $week,
        string $date,
        string $start,
        string $end,
        array $skills = [],
    ): Shift {
        $shift = (new Shift())
            ->setDate(new \DateTimeImmutable($date))
            ->setStartTime($start)
            ->setEndTime($end)
            ->setSkillsRequired($skills);

        $week->addShift($shift);

        return $shift;
    }
}