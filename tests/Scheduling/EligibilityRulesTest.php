<?php

namespace App\Tests\Scheduling;

use App\Entity\Account;
use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;
use App\Service\Impl\Scheduling\AssignedHoursTracker;
use App\Service\Impl\Scheduling\Rule\HasSkillRule;
use App\Service\Impl\Scheduling\Rule\IsActiveRule;
use App\Service\Impl\Scheduling\Rule\IsAvailableRule;
use App\Service\Impl\Scheduling\Rule\NotAlreadyAssignedRule;
use App\Service\Impl\Scheduling\Rule\WeeklyHoursLimitRule;
use PHPUnit\Framework\TestCase;

final class EligibilityRulesTest extends TestCase
{
    public function testIsActiveRule(): void
    {
        $rule = new IsActiveRule();
        $tracker = new AssignedHoursTracker();
        $shift = $this->makeShift('2026-11-23', '11:30', '15:00');
        $employee = $this->makeEmployee('a@example.com');

        self::assertTrue($rule->isSatisfiedBy($employee, $shift, $tracker));

        $employee->deactivate();
        self::assertFalse($rule->isSatisfiedBy($employee, $shift, $tracker));
    }

    public function testHasSkillRule(): void
    {
        $rule = new HasSkillRule();
        $tracker = new AssignedHoursTracker();
        $employee = $this->makeEmployee('a@example.com', skills: ['caisse', 'salle']);

        self::assertTrue($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-23', '11:30', '15:00', []), $tracker));
        self::assertTrue($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-23', '11:30', '15:00', ['caisse', 'salle']), $tracker));
        self::assertFalse($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-23', '11:30', '15:00', ['caisse', 'cuisine']), $tracker));
    }

    public function testIsAvailableRule(): void
    {
        $rule = new IsAvailableRule();
        $tracker = new AssignedHoursTracker();
        $employee = $this->makeEmployee('a@example.com', availableSlots: [1, 4], datesExclues: ['2026-11-30']);

        self::assertTrue($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-23', '10:30', '15:30'), $tracker));
        self::assertFalse($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-23', '17:30', '23:30'), $tracker));
        self::assertTrue($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-24', '18:00', '00:00'), $tracker));
        self::assertFalse($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-30', '11:30', '15:00'), $tracker));
    }

    public function testNotAlreadyAssignedRule(): void
    {
        $rule = new NotAlreadyAssignedRule();
        $tracker = new AssignedHoursTracker();
        $week = new Week();

        $lunch = $this->makeShift('2026-11-23', '11:30', '15:00', week: $week);
        $overlap = $this->makeShift('2026-11-23', '14:00', '16:00', week: $week);
        $dinner = $this->makeShift('2026-11-23', '18:00', '23:00', week: $week);
        $employee = $this->makeEmployee('a@example.com');

        self::assertTrue($rule->isSatisfiedBy($employee, $lunch, $tracker));

        $lunch->addAssignedEmployee($employee);

        self::assertFalse($rule->isSatisfiedBy($employee, $lunch, $tracker));
        self::assertFalse($rule->isSatisfiedBy($employee, $overlap, $tracker));
        self::assertTrue($rule->isSatisfiedBy($employee, $dinner, $tracker));
    }

    public function testWeeklyHoursLimitRule(): void
    {
        $rule = new WeeklyHoursLimitRule();
        $employee = $this->makeEmployee('a@example.com', weeklyHours: 5); // 300 min

        $existing = $this->makeShift('2026-11-23', '11:30', '15:00'); // 210 min
        $existing->addAssignedEmployee($employee);

        $tracker = AssignedHoursTracker::fromWeekShifts([$existing]);

        self::assertTrue($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-24', '11:30', '13:00'), $tracker)); // +90 = 300
        self::assertFalse($rule->isSatisfiedBy($employee, $this->makeShift('2026-11-24', '11:30', '13:30'), $tracker)); // +120 = 330
    }

    private function makeEmployee(
        string $email,
        int $weeklyHours = 35,
        array $availableSlots = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
        array $datesExclues = [],
        array $skills = [],
    ): Employee {
        $account = (new Account())->setEmail($email);

        return (new Employee(
            $account,
            'Test',
            'User',
            new \DateTimeImmutable('2024-01-01'),
            $weeklyHours,
        ))
            ->setAvailableSlots($availableSlots)
            ->setDatesExclues($datesExclues)
            ->setSkills($skills);
    }

    private function makeShift(
        string $date,
        string $start,
        string $end,
        array $skills = [],
        ?Week $week = null,
    ): Shift {
        $targetWeek = $week ?? new Week();
        $shift = (new Shift())
            ->setDate(new \DateTimeImmutable($date))
            ->setStartTime($start)
            ->setEndTime($end)
            ->setSkillsRequired($skills);

        $targetWeek->addShift($shift);

        return $shift;
    }
}