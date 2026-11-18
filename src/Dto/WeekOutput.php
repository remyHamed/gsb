<?php

namespace App\Dto;

use App\Entity\Week;

final class WeekOutput
{
    public static function toArray(Week $week): array
    {
        return [
            'id' => $week->getId()?->toRfc4122(),
            'year' => $week->getYear(),
            'weekNumber' => $week->getWeekNumber(),
            'startDate' => $week->getStartDate()?->format('Y-m-d'),
            'shiftCount' => $week->getShifts()->count(),
        ];
    }
}
