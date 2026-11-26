<?php

namespace App\Tests\Entity;

use App\Entity\Shift;
use App\Entity\Week;
use PHPUnit\Framework\TestCase;

final class ShiftTest extends TestCase
{
    public function testDurationInMinutes(): void
    {
        self::assertSame(210, $this->makeShift('2026-11-23', '11:30', '15:00')->durationInMinutes());
        self::assertSame(360, $this->makeShift('2026-11-23', '17:30', '23:30')->durationInMinutes());
        self::assertSame(360, $this->makeShift('2026-11-23', '18:00', '00:00')->durationInMinutes());
    }

    public function testResolveSlotNumber(): void
    {
        // Lundi = 1 (midi) et 2 (soir)
        self::assertSame(1, $this->makeShift('2026-11-23', '10:30', '15:30')->resolveSlotNumber());
        self::assertSame(2, $this->makeShift('2026-11-23', '17:30', '23:30')->resolveSlotNumber());

        // Mardi = 3 (midi) et 4 (soir jusqu'à minuit)
        self::assertSame(3, $this->makeShift('2026-11-24', '11:30', '16:00')->resolveSlotNumber());
        self::assertSame(4, $this->makeShift('2026-11-24', '18:00', '00:00')->resolveSlotNumber());

        // Dimanche = 13 (midi) et 14 (soir)
        self::assertSame(13, $this->makeShift('2026-11-29', '11:00', '15:00')->resolveSlotNumber());
        self::assertSame(14, $this->makeShift('2026-11-29', '18:00', '23:00')->resolveSlotNumber());
    }

    public function testOverlapsWith(): void
    {
        $lunch = $this->makeShift('2026-11-23', '11:30', '15:00');
        $overlappingLunch = $this->makeShift('2026-11-23', '14:00', '16:00');
        $adjacentDinner = $this->makeShift('2026-11-23', '17:30', '23:30');
        $sameHoursNextDay = $this->makeShift('2026-11-24', '11:30', '15:00');

        self::assertTrue($lunch->overlapsWith($overlappingLunch));
        self::assertFalse($lunch->overlapsWith($adjacentDinner));
        self::assertFalse($lunch->overlapsWith($sameHoursNextDay));
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