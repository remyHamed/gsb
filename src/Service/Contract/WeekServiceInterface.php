<?php

namespace App\Service\Contract;

use App\Dto\Week\WeekInput;
use App\Entity\Week;

interface WeekServiceInterface
{
    /** @return Week[] */
    public function findAll(): array;

    public function findById(string $id): Week;

    public function create(WeekInput $input): Week;

    public function update(string $id, WeekInput $input): Week;

    public function delete(string $id): void;
}
