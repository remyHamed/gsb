<?php

namespace App\Service\Contract\Scheduling;

use App\Entity\Employee;
use App\Entity\Week;

interface ScheduleGeneratorInterface
{
    /**
     * @param iterable<Employee> $employees
     */
    public function generate(Week $week, iterable $employees, ?int $maxPerShift = null): void;
}