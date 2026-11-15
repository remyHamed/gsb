<?php

namespace App\Repository\Impl;

use App\Entity\Employee;
use App\Repository\Contract\EmployeeRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Employee> */
class EmployeeRepository extends ServiceEntityRepository implements EmployeeRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function findActive(): array
    {
        return $this->findBy(['isActive' => true]);
    }
}
