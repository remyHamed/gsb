<?php

namespace App\Service\Impl\Scheduling\Rule;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Service\Contract\Scheduling\EligibilityRuleInterface;
use App\Service\Impl\Scheduling\AssignedHoursTracker;

final class NotAlreadyAssignedRule implements EligibilityRuleInterface
{
    public function isSatisfiedBy(Employee $employee, Shift $shift, AssignedHoursTracker $tracker): bool
    {
        if ($shift->getAssignedEmployees()->contains($employee)) {
            return false;
        }

        foreach ($shift->getWeek()?->getShifts() ?? [] as $otherShift) {
            if ($otherShift !== $shift
                && $otherShift->getAssignedEmployees()->contains($employee)
                && $shift->overlapsWith($otherShift)
            ) {
                return false;
            }
        }

        return true;
    }
}