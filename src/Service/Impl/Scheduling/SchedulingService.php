<?php

namespace App\Service\Impl\Scheduling;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;
use App\Exception\EmployeeNotFoundException;
use App\Exception\ShiftNotFoundException;
use App\Exception\WeekNotFoundException;
use App\Repository\Contract\EmployeeRepositoryInterface;
use App\Repository\Contract\ShiftRepositoryInterface;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Contract\Scheduling\EligibilityEvaluatorInterface;
use App\Service\Contract\Scheduling\ScheduleGeneratorInterface;
use App\Service\Contract\Scheduling\SchedulingServiceInterface;
use Symfony\Component\Uid\Uuid;

final class SchedulingService implements SchedulingServiceInterface
{
    public function __construct(
        private readonly WeekRepositoryInterface $weeks,
        private readonly ShiftRepositoryInterface $shifts,
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EligibilityEvaluatorInterface $evaluator,
        private readonly ScheduleGeneratorInterface $generator,
    ) {
    }

    public function getEligibleEmployeesForShift(string $shiftId): array
    {
        $shift = $this->findShiftOrFail($shiftId);
        $tracker = AssignedHoursTracker::fromWeekShifts($shift->getWeek()?->getShifts() ?? [$shift]);
        $eligible = $this->evaluator->filterEligible($this->employees->findAll(), $shift, $tracker);

        usort(
            $eligible,
            static fn (Employee $a, Employee $b): int =>
                [$a->getDateAnciennete(), $tracker->minutesFor($a)]
                <=>
                [$b->getDateAnciennete(), $tracker->minutesFor($b)]
        );

        return $eligible;
    }

    public function assignEmployee(string $shiftId, string $employeeId): Shift
    {
        $shift = $this->findShiftOrFail($shiftId);
        $employee = $this->findEmployeeOrFail($employeeId);
        $tracker = AssignedHoursTracker::fromWeekShifts($shift->getWeek()?->getShifts() ?? [$shift]);

        if ($this->evaluator->filterEligible([$employee], $shift, $tracker) === []) {
            throw new \DomainException('Employee is not eligible for this shift.');
        }

        $shift->addAssignedEmployee($employee);
        $this->shifts->save($shift);

        return $shift;
    }

    public function unassignEmployee(string $shiftId, string $employeeId): Shift
    {
        $shift = $this->findShiftOrFail($shiftId);
        $employee = $this->findEmployeeOrFail($employeeId);

        $shift->removeAssignedEmployee($employee);
        $this->shifts->save($shift);

        return $shift;
    }

    public function generateWeekSchedule(string $weekId, ?int $maxPerShift = null): Week
    {
        $week = $this->findWeekOrFail($weekId);

        $this->generator->generate($week, $this->employees->findAll(), $maxPerShift);

        foreach ($week->getShifts() as $shift) {
            $this->shifts->save($shift);
        }

        return $week;
    }

    private function findShiftOrFail(string $id): Shift
    {
        $shift = Uuid::isValid($id) ? $this->shifts->findById(Uuid::fromString($id)) : null;

        return $shift ?? throw new ShiftNotFoundException($id);
    }

    private function findEmployeeOrFail(string $id): Employee
    {
        $employee = Uuid::isValid($id) ? $this->employees->findById(Uuid::fromString($id)) : null;

        return $employee ?? throw new EmployeeNotFoundException($id);
    }

    private function findWeekOrFail(string $id): Week
    {
        $week = Uuid::isValid($id) ? $this->weeks->findById(Uuid::fromString($id)) : null;

        return $week ?? throw new WeekNotFoundException($id);
    }
}