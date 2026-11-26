<?php

namespace App\Tests\Entity;

use App\Entity\Account;
use App\Entity\Employee;
use PHPUnit\Framework\TestCase;

final class EmployeeTest extends TestCase
{
    public function testHasAllSkills(): void
    {
        $employee = $this->makeEmployee(skills: ['caisse', 'salle']);

        self::assertTrue($employee->hasAllSkills([]));
        self::assertTrue($employee->hasAllSkills(['caisse']));
        self::assertTrue($employee->hasAllSkills(['caisse', 'salle']));
        self::assertFalse($employee->hasAllSkills(['caisse', 'cuisine']));
    }

    public function testIsAvailableForSlotAndExcludedDates(): void
    {
        $employee = $this->makeEmployee(
            availableSlots: [1, 4],
            datesExclues: ['2026-11-30'],
        );

        self::assertTrue($employee->isAvailableFor(new \DateTimeImmutable('2026-11-23'), 1));
        self::assertFalse($employee->isAvailableFor(new \DateTimeImmutable('2026-11-23'), 2));
        self::assertFalse($employee->isAvailableFor(new \DateTimeImmutable('2026-11-30'), 1));
    }

    public function testWeeklyCapacityInMinutes(): void
    {
        self::assertSame(2100, $this->makeEmployee(weeklyHours: 35)->weeklyCapacityInMinutes());
        self::assertSame(600, $this->makeEmployee(weeklyHours: 10)->weeklyCapacityInMinutes());
    }

    private function makeEmployee(
        int $weeklyHours = 35,
        array $availableSlots = [],
        array $datesExclues = [],
        array $skills = [],
    ): Employee {
        $account = (new Account())->setEmail('emp@example.com');

        return (new Employee(
            $account,
            'Alice',
            'Martin',
            new \DateTimeImmutable('2024-01-01'),
            $weeklyHours,
        ))
            ->setAvailableSlots($availableSlots)
            ->setDatesExclues($datesExclues)
            ->setSkills($skills);
    }
}