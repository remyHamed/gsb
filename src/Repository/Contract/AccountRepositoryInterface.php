<?php

namespace App\Repository\Contract;

use App\Entity\Account;

interface AccountRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    public function findOneByEmail(string $email): ?Account;

    public function save(Account $entity, bool $flush = false): void;
}
