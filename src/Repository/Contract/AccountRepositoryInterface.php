<?php

namespace App\Repository\Contract;

use App\Entity\Account;
use Symfony\Component\Uid\Uuid;

interface AccountRepositoryInterface
{
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    public function findOneByEmail(string $email): ?Account;

    public function findById(Uuid $id): ?Account;

    public function save(Account $entity, bool $flush = false): void;
}