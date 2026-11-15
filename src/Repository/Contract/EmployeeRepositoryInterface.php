<?php

namespace App\Repository\Contract;

use App\Entity\Employee;

interface EmployeeRepositoryInterface
{
    /** @return Employee[] */
    public function findAll(): array;

    /** @return Employee[] */
    public function findActive(): array;
}
