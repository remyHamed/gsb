<?php

namespace App\Repository;

use App\Entity\Account;

interface AccountRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;
    
    public function save(Account $entity, bool $flush = false): void;
}   