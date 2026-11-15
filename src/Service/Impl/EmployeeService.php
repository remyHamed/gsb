<?php

namespace App\Service\Impl;

use App\Repository\Contract\EmployeeRepositoryInterface;
use App\Service\Contract\EmployeeServiceInterface;

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
}