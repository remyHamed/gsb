<?php

namespace App\Service\Impl;

use App\Dto\Employee\EmployeeInput;
use App\Entity\Account;
use App\Entity\Employee;
use App\Exception\EmployeeNotFoundException;
use App\Exception\AccountNotFoundException;
use App\Exception\EmployeeAlreadyExistsException;
use App\Repository\Contract\EmployeeRepositoryInterface;
use App\Service\Contract\EmployeeServiceInterface;
use App\Repository\Contract\AccountRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Uuid;

class EmployeeService implements EmployeeServiceInterface
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $employeeRepository,
        private readonly AccountRepositoryInterface $accountRepository,
    ) {}

    public function findAll(): array
    {
        return $this->employeeRepository->findAll();
    }

    public function findActive(): array
    {
        return $this->employeeRepository->findActive();
    }

    public function findById(string $id): Employee
    {
        $employee = Uuid::isValid($id) ? $this->employeeRepository->findById(Uuid::fromString($id)) : null;

        return $employee ?? throw new EmployeeNotFoundException($id);
    }

    public function create(EmployeeInput $input): Employee
    {
        $account = $this->accountRepository->findById(Uuid::fromString($input->accountId))?? throw new AccountNotFoundException($input->accountId);

        $accountId = $account->getId();
        if ($accountId !== null && $this->employeeRepository->existsByAccount($accountId)) {
            throw new EmployeeAlreadyExistsException('Employee already exists for this account.');
        }

        $seniority = new \DateTimeImmutable($input->dateAnciennete);
        $this->assertNoDuplicate($account, $input, $seniority, null);

        $employee = new Employee(
            $account,
            $input->firstName,
            $input->lastName,
            $seniority,
            $input->weeklyHours
        );

        $employee->setAvailableSlots($input->joursDisponibles ?? []);
        $employee->setDatesExclues($input->datesExclues ?? []);
        $employee->setSkills($input->skills ?? []);

        $this->employeeRepository->save($employee);

        return $employee;
    }

    public function update(string $id, EmployeeInput $input): Employee
    {
        $employee = $this->findById($id);
        $seniority = new \DateTimeImmutable($input->dateAnciennete);

        $account = $employee->getAccount();
        if (!$account) {
            throw new AccountNotFoundException('Account not found for this employee.');
        }

        $this->assertNoDuplicate($account, $input, $seniority, $employee->getId());

        $employee->update(
            $input->firstName,
            $input->lastName,
            $seniority,
            $input->weeklyHours,
            $input->joursDisponibles ?? [],
            $input->datesExclues ?? [],
            $input->skills ?? []
        );

        $this->employeeRepository->save($employee);

        return $employee;
    }

    public function delete(string $id): void
    {
        $employee = $this->findById($id);
        $this->employeeRepository->remove($employee);
    }

    private function assertNoDuplicate(Account $account, EmployeeInput $input, \DateTimeImmutable $seniority, ?Uuid $excludeId): void
    {
        $accountId = $account->getId();
        
        if ($accountId === null) {
            throw new \LogicException('Un compte doit obligatoirement posséder un ID valide pour vérifier les doublons.');
        }

        if ($this->employeeRepository->existsDuplicate($accountId, $input->firstName, $input->lastName, $seniority, $excludeId)) {
            throw new EmployeeAlreadyExistsException('Un employé avec les mêmes informations existe déjà.');
        }
    }
    public function deactivate(string $id): Employee
    {
        $employee = $this->findById($id);

        if ($employee->isActive()) {
            $employee->deactivate();
            $this->employeeRepository->save($employee);
        }

        return $employee;
    }

    public function activate(string $id): Employee
    {
        $employee = $this->findById($id);
        
        $employee->activate();
        
        $this->employeeRepository->save($employee);

        return $employee;
    }
}