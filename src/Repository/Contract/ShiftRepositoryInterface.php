<?php

namespace App\Repository\Contract;

use App\Entity\Shift;
use Symfony\Component\Uid\Uuid;

interface ShiftRepositoryInterface
{
    /** @return Shift[] */
    public function findAll(): array;

    /** @return Shift[] */
    public function findByWeek(Uuid $weekId): array;

    public function findById(Uuid $id): ?Shift;

    public function save(Shift $shift):void;
    
    public function remove(Shift $shift): void;
}