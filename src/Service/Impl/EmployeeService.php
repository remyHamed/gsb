<?php

namespace App\Service\Impl;

use App\Dto\EmployeeInput;
use App\Entity\Account;
use App\Entity\Employee;
use App\Exception\EmployeeNotFoundException;
use App\Repository\Contract\EmployeeRepositoryInterface;
use App\Service\Contract\EmployeeServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class EmployeeService implements EmployeeServiceInterface
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly EntityManagerInterface $em, // Replace with AccountRepositoryInterface once created
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
        $employee = Uuid::isValid($id) ? $this->employees->findById(Uuid::fromString($id)) : null;

        return $employee ?? throw new EmployeeNotFoundException($id);
    }

    public function create(EmployeeInput $input): Employee
    {
        $account = $this->accounts->findById(Uuid::fromString($input->accountId))
            ?? throw new AccountNotFoundException($input->accountId);

        $seniority = new \DateTimeImmutable($input->dateAnciennete);
        $this->assertNoDuplicate($account, $input, $seniority, null);

        $employee = new Employee($account, $input->firstName, $input->lastName, $seniority /* ... */);
        $this->employees->save($employee);

        return $employee;
    }

    public function update(string $id, EmployeeInput $input): Employee
    {
        $employee = $this->findById($id);
        $seniority = new \DateTimeImmutable($input->dateAnciennete);

        $this->assertNoDuplicate($employee->getAccount(), $input, $seniority, $employee->getId());

        $employee->update($input->firstName, $input->lastName, $seniority /* ... */);
        $this->employees->save($employee);

        return $employee;
    }

    public function delete(string $id): void
    {
        $employee = $this->findById($id);
        $this->employees->remove($employee);
    }

    private function assertNoDuplicate(Account $account, EmployeeInput $input, \DateTimeImmutable $seniority, ?Uuid $excludeId): void
    {
        if ($this->employees->existsDuplicate($account->getId(), $input->firstName, $input->lastName, $seniority, $excludeId)) {
            throw new EmployeeAlreadyExistsException('Employee already exists.');
        }
    }
}