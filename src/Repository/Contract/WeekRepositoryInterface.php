<?php

namespace App\Repository\Contract;

use App\Entity\Week;
use App\Exception\WeekAlreadyExistsException;
use Symfony\Component\Uid\Uuid;

interface WeekRepositoryInterface
{
    /** @return Week[] */
    public function findAll(): array;

    public function findById(Uuid $id): ?Week;

    public function existsDuplicate(int $year, int $weekNumber, ?Uuid $excludeId): bool;

    public function overlapsPeriod(\DateTimeImmutable $start, \DateTimeImmutable $end, ?Uuid $excludeId = null): bool;
    
    /** @throws WeekAlreadyExistsException */
    public function save(Week $week): void;

    public function remove(Week $week): void;
}
