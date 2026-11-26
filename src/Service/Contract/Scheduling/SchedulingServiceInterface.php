<?php

namespace App\Service\Contract\Scheduling;

use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;

interface SchedulingServiceInterface
{
    /**
     * @return Employee[]
     */
    public function getEligibleEmployeesForShift(string $shiftId): array;

    public function assignEmployee(string $shiftId, string $employeeId): Shift;

    public function unassignEmployee(string $shiftId, string $employeeId): Shift;

    public function generateWeekSchedule(string $weekId, ?int $maxPerShift = null): Week;
}