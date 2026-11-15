<?php

namespace App\Service\Contract;

use App\Entity\Employee;

interface EmployeeServiceInterface
{
    /** @return Employee[] */
    public function findAll(): array;

    /** @return Employee[] */
    public function findActive(): array;
}