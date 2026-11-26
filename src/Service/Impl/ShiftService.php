<?php

namespace App\Service\Impl;

use App\Exception\WeekNotFoundException;
use App\Repository\Contract\ShiftRepositoryInterface;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Contract\ShiftServiceInterface;
use App\Exception\ShiftNotFoundException;
use App\Entity\Shift;
use Symfony\Component\Uid\Uuid;
use App\Dto\Shift\ShiftInput;

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

    public function findById(string $id) : Shift
    {

        $shift = Uuid::isValid($id) ? $this->shifts->findById(Uuid::fromString($id)) : null;
        
        return $shift ?? throw new ShiftNotFoundException($id);
        
    }

    public function create(ShiftInput $input): Shift
    {
        if (!Uuid::isValid($input->weekId)) {
            throw new WeekNotFoundException($input->weekId);
        }

        $week = $this->weeks->findById(Uuid::fromString($input->weekId)) ?? throw new WeekNotFoundException($input->weekId);

        $shift = new Shift();
        $shift
            ->setDate(new \DateTimeImmutable($input->date))
            ->setStartTime($input->startTime)
            ->setEndTime($input->endTime)
            ->setSkillsRequired($input->skillsRequired)
            ->setWeek($week);

        $this->shifts->save($shift);

        return $shift;
    }

    public function update(string $shiftId, ShiftInput $input): Shift
    {
        if (!Uuid::isValid($shiftId)) {
            throw new ShiftNotFoundException($shiftId);
        }

        if (!Uuid::isValid($input->weekId)) {
            throw new WeekNotFoundException($input->weekId);
        }

        $shift = $this->findById($shiftId);

        $week = $this->weeks->findById(Uuid::fromString($input->weekId))
            ?? throw new WeekNotFoundException($input->weekId);

        $shift
            ->setDate(new \DateTimeImmutable($input->date))
            ->setStartTime($input->startTime)
            ->setEndTime($input->endTime)
            ->setSkillsRequired($input->skillsRequired)
            ->setWeek($week);

        $this->shifts->save($shift);

        return $shift;
    }
}