<?php

namespace App\Tests\Service\Scheduling;

use App\Entity\Account;
use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;
use App\Service\Impl\Scheduling\AssignedHoursTracker;
use PHPUnit\Framework\TestCase;

final class AssignedHoursTrackerTest extends TestCase
{
    public function testInitializesFromExistingWeekShiftsAndAddsMinutes(): void
    {
        $alice = $this->makeEmployee('alice@example.com');
        $bob = $this->makeEmployee('bob@example.com');

        $shift1 = $this->makeShift('2026-11-23', '11:30', '15:30'); // 240 min
        $shift1->addAssignedEmployee($alice);

        $shift2 = $this->makeShift('2026-11-23', '17:30', '23:30'); // 360 min

        $tracker = AssignedHoursTracker::fromWeekShifts([$shift1, $shift2]);

        self::assertSame(240, $tracker->minutesFor($alice));
        self::assertSame(0, $tracker->minutesFor($bob));

        $tracker->addMinutes($alice, $shift2->durationInMinutes());
        self::assertSame(600, $tracker->minutesFor($alice));
    }

    private function makeEmployee(string $email): Employee
    {
        $account = (new Account())->setEmail($email);

        return new Employee(
            $account,
            'Test',
            'User',
            new \DateTimeImmutable('2024-01-01'),
            35,
        );
    }

    private function makeShift(string $date, string $start, string $end): Shift
    {
        return (new Shift())
            ->setDate(new \DateTimeImmutable($date))
            ->setStartTime($start)
            ->setEndTime($end)
            ->setWeek(new Week());
    }
}