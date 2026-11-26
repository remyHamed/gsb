<?php

namespace App\Dto\Week;

use App\Dto\Shift\ShiftOutput;
use App\Entity\Shift;
use App\Entity\Week;

final class WeekOutput
{
    public static function toArray(Week $week, bool $includeShifts = false): array
    {
        $data = [
            'id' => $week->getId()?->toRfc4122(),
            'year' => $week->getYear(),
            'weekNumber' => $week->getWeekNumber(),
            'startDate' => $week->getStartDate()?->format('Y-m-d'),
            'shiftCount' => $week->getShifts()->count(),
        ];

        if ($includeShifts) {
            $shifts = $week->getShifts()->toArray();
            usort(
                $shifts,
                static fn (Shift $a, Shift $b): int =>
                    [$a->getDate()?->format('Y-m-d'), $a->getStartTime()]
                    <=>
                    [$b->getDate()?->format('Y-m-d'), $b->getStartTime()]
            );

            $data['shifts'] = array_map(ShiftOutput::toArray(...), $shifts);
        }

        return $data;
    }
}