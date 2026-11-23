<?php

namespace App\Service\Impl;

use App\Exception\WeekNotFoundException;
use App\Repository\Contract\ShiftRepositoryInterface;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Contract\ShiftServiceInterface;
use App\Exception\ShiftNotFoundException;
use App\Entity\Shift;
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

    public function findById(string $id) : Shift
    {

        $shift = Uuid::isValid($id) ? $this->shifts->findById(Uuid::fromString($id)) : null;
        
        return $shift ?? throw new ShiftNotFoundException($id);
        
    }
}