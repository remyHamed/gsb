<?php

namespace App\Service\Impl;

use App\Entity\Employee;
use App\Exception\EmployeeNotFoundException;
use App\Repository\Contract\EmployeeRepositoryInterface;
use App\Service\Contract\EmployeeServiceInterface;
use Symfony\Component\Uid\Uuid;

class EmployeeService implements EmployeeServiceInterface
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
    ) {
    }

    public function findAll(): array
    {
        return $this->employees->findAll();
    }

    public function findActive(): array
    {
        return $this->employees->findActive();
    }

    public function findById(string $id): Employee
    {
        if (!Uuid::isValid($id)) {
            throw new EmployeeNotFoundException($id);
        }

        $employee = $this->employees->find($id);

        if (!$employee instanceof Employee) {
            throw new EmployeeNotFoundException($id);
        }

        return $employee;
    }
}
