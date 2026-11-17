<?php

namespace App\Repository\Contract;

use App\Entity\Employee;
use Symfony\Component\Uid\Uuid;

interface EmployeeRepositoryInterface
{
    /** @return Employee[] */
    public function findAll(): array;

    /** @return Employee[] */
    public function findActive(): array;

    public function findById(Uuid $id): ?Employee;

    public function existsDuplicate(Uuid $accountId, string $firstName, string $lastName, \DateTimeImmutable $dateAnciennete, ?Uuid $excludeId): bool;

    public function existsByAccount(Uuid $accountId): bool;
    
    /** @throws EmployeeAlreadyExistsException */
    public function save(Employee $employee): void;

    /** @throws EmployeeInUseException */
    public function remove(Employee $employee): void;
}