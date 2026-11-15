<?php

namespace App\Dto;

use App\Entity\Employee;

final class EmployeeOutput
{
    public static function toArray(Employee $employee): array
    {
        return [
            'id' => $employee->getId()?->toRfc4122(),
            'firstName' => $employee->getFirstName(),
            'lastName' => $employee->getLastName(),
            'dateAnciennete' => $employee->getDateAnciennete()?->format('Y-m-d'),
            'weeklyHours' => $employee->getWeeklyHours(),
            'availableSlots' => $employee->getAvailableSlots(),
            'datesExclues' => $employee->getDatesExclues(),
            'skills' => $employee->getSkills(),
            'isActive' => $employee->isActive(),
            'accountId' => $employee->getAccount()?->getId()?->toRfc4122(),
            'createdAt' => $employee->getCreatedAt()?->format(\DATE_ATOM),
        ];
    }
}
