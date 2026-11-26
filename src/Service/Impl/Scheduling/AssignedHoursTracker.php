<?php

namespace App\Service\Impl\Scheduling;

use App\Entity\Employee;
use App\Entity\Shift;

final class AssignedHoursTracker
{
    /** @var array<string, int> */
    private array $minutesByEmployee = [];

    /**
     * @param iterable<Shift> $shifts
     */
    public static function fromWeekShifts(iterable $shifts): self
    {
        $tracker = new self();

        foreach ($shifts as $shift) {
            foreach ($shift->getAssignedEmployees() as $employee) {
                $tracker->addMinutes($employee, $shift->durationInMinutes());
            }
        }

        return $tracker;
    }

    public function minutesFor(Employee $employee): int
    {
        return $this->minutesByEmployee[$this->key($employee)] ?? 0;
    }

    public function addMinutes(Employee $employee, int $minutes): void
    {
        $key = $this->key($employee);
        $this->minutesByEmployee[$key] = ($this->minutesByEmployee[$key] ?? 0) + $minutes;
    }

    private function key(Employee $employee): string
    {
        return $employee->getId()?->toRfc4122() ?? (string) spl_object_id($employee);
    }
}