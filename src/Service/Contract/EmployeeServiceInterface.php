<?php

namespace App\Service\Contract;

use App\Dto\EmployeeInput;
use App\Entity\Employee;

interface EmployeeServiceInterface
{
    /** @return Employee[] */
    public function findAll(): array;

    /** @return Employee[] */
    public function findActive(): array;

    /** @throws \App\Exception\EmployeeNotFoundException */
    public function findById(string $id): Employee;

    public function create(EmployeeInput $input): Employee;

    /** @throws \App\Exception\EmployeeNotFoundException */
    public function update(string $id, EmployeeInput $input): Employee;

    /** @throws \App\Exception\EmployeeNotFoundException */
    public function delete(string $id): void;
}