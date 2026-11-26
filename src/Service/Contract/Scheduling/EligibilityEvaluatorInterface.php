<?php

namespace App\Service\Contract\Scheduling;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Service\Impl\Scheduling\AssignedHoursTracker;

interface EligibilityEvaluatorInterface
{
    /**
     * @param iterable<Employee> $employees
     * @return Employee[]
     */
    public function filterEligible(iterable $employees, Shift $shift, AssignedHoursTracker $tracker): array;
}