<?php

namespace App\Service\Contract\Scheduling;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Service\Impl\Scheduling\AssignedHoursTracker;

interface EligibilityRuleInterface
{
    public function isSatisfiedBy(Employee $employee, Shift $shift, AssignedHoursTracker $tracker): bool;
}