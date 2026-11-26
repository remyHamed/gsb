<?php

namespace App\Service\Impl\Scheduling\Rule;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Service\Contract\Scheduling\EligibilityRuleInterface;
use App\Service\Impl\Scheduling\AssignedHoursTracker;

final class HasSkillRule implements EligibilityRuleInterface
{
    public function isSatisfiedBy(Employee $employee, Shift $shift, AssignedHoursTracker $tracker): bool
    {
        return $employee->hasAllSkills($shift->getSkillsRequired());
    }
}