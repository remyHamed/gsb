<?php

namespace App\Service\Impl\Scheduling;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;
use App\Service\Contract\Scheduling\EligibilityEvaluatorInterface;
use App\Service\Contract\Scheduling\ScheduleGeneratorInterface;

final class ScheduleGenerator implements ScheduleGeneratorInterface
{
    public function __construct(
        private readonly EligibilityEvaluatorInterface $evaluator,
    ) {
    }

    public function generate(Week $week, iterable $employees, ?int $maxPerShift = null): void
    {
        $candidateList = is_array($employees) ? $employees : iterator_to_array($employees);
        $shifts = $this->sortShiftsChronologically($week->getShifts()->toArray());
        $tracker = AssignedHoursTracker::fromWeekShifts($shifts);

        foreach ($shifts as $shift) {
            $eligible = $this->evaluator->filterEligible($candidateList, $shift, $tracker);
            $prioritized = $this->sortByPriority($eligible, $tracker);

            foreach ($prioritized as $employee) {
                if ($maxPerShift !== null && $shift->getAssignedEmployees()->count() >= $maxPerShift) {
                    break;
                }

                $shift->addAssignedEmployee($employee);
                $tracker->addMinutes($employee, $shift->durationInMinutes());
            }
        }
    }

    /**
     * @param Shift[] $shifts
     * @return Shift[]
     */
    private function sortShiftsChronologically(array $shifts): array
    {
        usort(
            $shifts,
            static fn (Shift $a, Shift $b): int =>
                [$a->getDate()->format('Y-m-d'), $a->getStartTime()]
                <=>
                [$b->getDate()->format('Y-m-d'), $b->getStartTime()]
        );

        return $shifts;
    }

    /**
     * @param Employee[] $employees
     * @return Employee[]
     */
    private function sortByPriority(array $employees, AssignedHoursTracker $tracker): array
    {
        usort(
            $employees,
            static fn (Employee $a, Employee $b): int =>
                [$a->getDateAnciennete(), $tracker->minutesFor($a)]
                <=>
                [$b->getDateAnciennete(), $tracker->minutesFor($b)]
        );

        return $employees;
    }
}