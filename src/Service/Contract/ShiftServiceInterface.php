<?php

namespace App\Service\Contract;

use App\Entity\Shift;
use App\Exception\WeekNotFoundException;

interface ShiftServiceInterface
{
    /** @return Shift[] */
    public function findAll(): array;

    /**
     * @return Shift[]
     *
     * @throws WeekNotFoundException
     */
    public function findByWeek(string $weekId): array;

    public function findById(string $id) : Shift;
}