<?php

namespace App\Service\Impl;

use App\Dto\Shift\ShiftInput;
use App\Entity\Shift;
use App\Entity\Week;
use App\Exception\ShiftNotFoundException;
use App\Exception\WeekNotFoundException;
use App\Repository\Contract\ShiftRepositoryInterface;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Contract\ShiftServiceInterface;
use Symfony\Component\Uid\Uuid;

final class ShiftService implements ShiftServiceInterface
{
    public function __construct(
        private readonly ShiftRepositoryInterface $shifts,
        private readonly WeekRepositoryInterface $weeks,
    ) {
    }

    public function findAll(): array
    {
        return $this->shifts->findAll();
    }

    public function findByWeek(string $weekId): array
    {
        if (!Uuid::isValid($weekId)) {
            throw new WeekNotFoundException($weekId);
        }

        $id = Uuid::fromString($weekId);

        if ($this->weeks->findById($id) === null) {
            throw new WeekNotFoundException($weekId);
        }

        return $this->shifts->findByWeek($id);
    }

    public function findById(string $id): Shift
    {
        $shift = Uuid::isValid($id) ? $this->shifts->findById(Uuid::fromString($id)) : null;

        return $shift ?? throw new ShiftNotFoundException($id);
    }

    public function create(ShiftInput $input): Shift
    {
        if (!Uuid::isValid($input->weekId)) {
            throw new WeekNotFoundException($input->weekId);
        }

        $week = $this->weeks->findById(Uuid::fromString($input->weekId))
            ?? throw new WeekNotFoundException($input->weekId);

        $date = new \DateTimeImmutable($input->date);

        $this->validateShiftRules($week, $date, $input->startTime, $input->endTime);

        $shift = (new Shift())
            ->setDate($date)
            ->setStartTime($input->startTime)
            ->setEndTime($input->endTime)
            ->setSkillsRequired($input->skillsRequired)
            ->setWeek($week);

        $this->shifts->save($shift);

        return $shift;
    }

    public function update(string $shiftId, ShiftInput $input): Shift
    {
        $shift = $this->findById($shiftId);

        if (!Uuid::isValid($input->weekId)) {
            throw new WeekNotFoundException($input->weekId);
        }

        $week = $this->weeks->findById(Uuid::fromString($input->weekId))
            ?? throw new WeekNotFoundException($input->weekId);

        if ($shift->getWeek()->getId() !== null && !$shift->getWeek()->getId()->equals($week->getId())) {
            throw new \DomainException('Cannot move a shift to another week.');
        }

        $date = new \DateTimeImmutable($input->date);

        $this->validateShiftRules($week, $date, $input->startTime, $input->endTime, $shift->getId());

        $shift
            ->setDate($date)
            ->setStartTime($input->startTime)
            ->setEndTime($input->endTime)
            ->setSkillsRequired($input->skillsRequired)
            ->setWeek($week);

        $this->shifts->save($shift);

        return $shift;
    }

    private function validateShiftRules(
        Week $week,
        \DateTimeImmutable $date,
        string $startTime,
        string $endTime,
        ?Uuid $excludeShiftId = null,
    ): void {
        if ($endTime <= $startTime) {
            throw new \DomainException('End time must be after start time.');
        }

        $weekStart = $week->getStartDate()->setTime(0, 0);
        $weekEnd = $weekStart->modify('+6 days');
        $normalizedDate = $date->setTime(0, 0);

        if ($normalizedDate < $weekStart || $normalizedDate > $weekEnd) {
            throw new \DomainException('Shift date must fall within the target week.');
        }

        $weekId = $week->getId();
        $weekShifts = $weekId !== null ? $this->shifts->findByWeek($weekId) : [];

        $dailyCount = 0;
        $weeklyCount = 0;

        foreach ($weekShifts as $existingShift) {
            if ($excludeShiftId !== null && $existingShift->getId()?->equals($excludeShiftId)) {
                continue;
            }

            $weeklyCount++;

            if ($existingShift->getDate()->format('Y-m-d') === $normalizedDate->format('Y-m-d')) {
                $dailyCount++;

                if ($startTime < $existingShift->getEndTime() && $endTime > $existingShift->getStartTime()) {
                    throw new \DomainException('Shift overlaps with an existing shift on this date.');
                }
            }
        }

        if ($dailyCount >= 2) {
            throw new \DomainException('Daily quota of 2 shifts reached.');
        }

        if ($weeklyCount >= 14) {
            throw new \DomainException('Weekly quota of 14 shifts reached.');
        }
    }
}