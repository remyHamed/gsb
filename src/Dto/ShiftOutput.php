<?php

namespace App\Dto;

use App\Entity\Employee;
use App\Entity\Shift;

final class ShiftOutput
{
    public static function toArray(Shift $shift): array
    {
        return [
            'id' => $shift->getId()?->toRfc4122(),
            'date' => $shift->getDate()?->format('Y-m-d'),
            'startTime' => $shift->getStartTime(),
            'endTime' => $shift->getEndTime(),
            'skillsRequired' => $shift->getSkillsRequired(),
            'weekId' => $shift->getWeek()?->getId()?->toRfc4122(),
            'assignedEmployeeIds' => array_values($shift->getAssignedEmployees()->map(
                static fn (Employee $employee): ?string => $employee->getId()?->toRfc4122()
            )->toArray()),
        ];
    }
}