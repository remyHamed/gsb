<?php

namespace App\Tests\Scheduling;

use App\Entity\Account;
use App\Entity\Employee;
use App\Entity\Shift;
use App\Entity\Week;
use App\Service\Impl\Scheduling\AssignedHoursTracker;
use App\Service\Impl\Scheduling\EligibilityEvaluator;
use App\Service\Impl\Scheduling\Rule\HasSkillRule;
use App\Service\Impl\Scheduling\Rule\IsActiveRule;
use App\Service\Impl\Scheduling\Rule\IsAvailableRule;
use App\Service\Impl\Scheduling\Rule\NotAlreadyAssignedRule;
use App\Service\Impl\Scheduling\Rule\WeeklyHoursLimitRule;
use App\Service\Impl\Scheduling\ScheduleGenerator;
use PHPUnit\Framework\TestCase;

final class ScheduleGeneratorTest extends TestCase
{
    private function createEvaluator(): EligibilityEvaluator
    {
        return new EligibilityEvaluator([
            new IsActiveRule(),
            new HasSkillRule(),
            new IsAvailableRule(),
            new NotAlreadyAssignedRule(),
            new WeeklyHoursLimitRule(),
        ]);
    }

    public function testEvaluatorFiltersOutIneligibleEmployees(): void
    {
        $evaluator = $this->createEvaluator();
        $week = new Week();
        $shift = $this->makeShift($week, '2026-11-23', '11:30', '15:00', ['salle']);

        $eligible = $this->makeEmployee('ok@example.com', skills: ['salle'], availableSlots: [1]);
        $inactive = $this->makeEmployee('inactive@example.com', skills: ['salle'], availableSlots: [1])->deactivate();
        $wrongSkill = $this->makeEmployee('noskill@example.com', skills: ['cuisine'], availableSlots: [1]);
        $unavailable = $this->makeEmployee('unavail@example.com', skills: ['salle'], availableSlots: [2]);

        $tracker = AssignedHoursTracker::fromWeekShifts([$shift]);

        $result = $evaluator->filterEligible(
            [$eligible, $inactive, $wrongSkill, $unavailable],
            $shift,
            $tracker,
        );

        self::assertSame([$eligible], $result);
    }

    public function testGeneratorPrioritizesSeniorEmployeesFirst(): void
    {
        $generator = new ScheduleGenerator($this->createEvaluator());
        $week = new Week();
        $shift = $this->makeShift($week, '2026-11-23', '11:30', '15:30');

        $junior = $this->makeEmployee('junior@example.com', dateAnciennete: '2025-06-01');
        $senior = $this->makeEmployee('senior@example.com', dateAnciennete: '2021-03-15');

        $generator->generate($week, [$junior, $senior], maxPerShift: 1);

        self::assertCount(1, $shift->getAssignedEmployees());
        self::assertTrue($shift->getAssignedEmployees()->contains($senior));
        self::assertFalse($shift->getAssignedEmployees()->contains($junior));
    }

    public function testGeneratorAssignsAllEligibleEmployeesUntilWeeklyCapIsReached(): void
    {
        $generator = new ScheduleGenerator($this->createEvaluator());
        $week = new Week();

        $lunch = $this->makeShift($week, '2026-11-23', '11:30', '15:30');
        $dinner = $this->makeShift($week, '2026-11-23', '17:30', '23:30');

        $alice = $this->makeEmployee('alice@example.com', weeklyHours: 8, availableSlots: [1, 2]);
        $bob = $this->makeEmployee('bob@example.com', weeklyHours: 35, availableSlots: [1, 2]);

        $generator->generate($week, [$alice, $bob]);

        self::assertCount(2, $lunch->getAssignedEmployees());
        self::assertTrue($lunch->getAssignedEmployees()->contains($alice));
        self::assertTrue($lunch->getAssignedEmployees()->contains($bob));

        self::assertCount(1, $dinner->getAssignedEmployees());
        self::assertFalse($dinner->getAssignedEmployees()->contains($alice));
        self::assertTrue($dinner->getAssignedEmployees()->contains($bob));
    }

    public function testGeneratorPreservesExistingManualAssignmentsAndBalancesRemainingHours(): void
    {
        $generator = new ScheduleGenerator($this->createEvaluator());
        $week = new Week();

        $mondayLunch = $this->makeShift($week, '2026-11-23', '11:30', '15:30');
        $tuesdayLunch = $this->makeShift($week, '2026-11-24', '11:30', '15:30');

        $alice = $this->makeEmployee('alice@example.com', weeklyHours: 6, availableSlots: [1, 3]);
        $mondayLunch->addAssignedEmployee($alice);

        $bob = $this->makeEmployee('bob@example.com', weeklyHours: 20, availableSlots: [3]);

        $generator->generate($week, [$alice, $bob]);

        self::assertCount(1, $mondayLunch->getAssignedEmployees());
        self::assertTrue($mondayLunch->getAssignedEmployees()->contains($alice));

        self::assertCount(1, $tuesdayLunch->getAssignedEmployees());
        self::assertTrue($tuesdayLunch->getAssignedEmployees()->contains($bob));
    }

    private function makeEmployee(
        string $email,
        int $weeklyHours = 35,
        array $availableSlots = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14],
        array $skills = [],
        string $dateAnciennete = '2024-01-01',
    ): Employee {
        $account = (new Account())->setEmail($email);

        return (new Employee(
            $account,
            'Test',
            'User',
            new \DateTimeImmutable($dateAnciennete),
            $weeklyHours,
        ))
            ->setAvailableSlots($availableSlots)
            ->setSkills($skills);
    }

    private function makeShift(
        Week $week,
        string $date,
        string $start,
        string $end,
        array $skills = [],
    ): Shift {
        $shift = (new Shift())
            ->setDate(new \DateTimeImmutable($date))
            ->setStartTime($start)
            ->setEndTime($end)
            ->setSkillsRequired($skills);

        $week->addShift($shift);

        return $shift;
    }
}