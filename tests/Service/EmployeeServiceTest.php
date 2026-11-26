<?php

namespace App\Tests\Service;

use App\Dto\Employee\EmployeeInput;
use App\Entity\Account;
use App\Entity\Employee;
use App\Exception\AccountNotFoundException;
use App\Exception\EmployeeAlreadyExistsException;
use App\Exception\EmployeeInUseException;
use App\Exception\EmployeeNotFoundException;
use App\Repository\Contract\AccountRepositoryInterface;
use App\Repository\Contract\EmployeeRepositoryInterface;
use App\Service\Impl\EmployeeService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
final class EmployeeServiceTest extends TestCase
{
    private const EMPLOYEE_ID = '018f0000-0000-7000-8000-000000000001';
    private const ACCOUNT_ID = '018f0000-0000-7000-8000-0000000000a1';
    private const OTHER_ACCOUNT_ID = '018f0000-0000-7000-8000-0000000000b2';

    private EmployeeRepositoryInterface|MockObject $employees;
    private AccountRepositoryInterface|MockObject $accounts;
    private EmployeeService $service;

    protected function setUp(): void
    {
        $this->employees = $this->createMock(EmployeeRepositoryInterface::class);
        $this->accounts = $this->createMock(AccountRepositoryInterface::class);

        $this->service = new EmployeeService(
            $this->employees,
            $this->accounts
        );
    }

    // ---------- Fixtures (seul endroit à adapter quand l'entité / le DTO changent) ----------

    private function account(?string $id = null): Account
    {
        $account = (new Account())
            ->setEmail('alice@example.com')
            ->setPasswordHash('x');

        // L'id est normalement généré par Doctrine : on le force par réflexion.
        (new \ReflectionProperty(Account::class, 'id'))
            ->setValue($account, $id !== null ? Uuid::fromString($id) : Uuid::v7());

        return $account;
    }

    private function employee(bool $active = true): Employee
    {
        $employee = new Employee(
            $this->account(),
            'Alice',
            'Martin',
            new \DateTimeImmutable('2024-01-01'),
            35
        );

        $employee->setIsActive($active);

        return $employee;
    }

    private function input(array $overrides = []): EmployeeInput
    {
        return new EmployeeInput(...array_merge([
            'firstName' => 'Coreli',
            'lastName' => 'Martin',
            'dateAnciennete' => '2026-07-13',
            'weeklyHours' => 20,
            'joursDisponibles' => [1, 2, 3],
            'datesExclues' => ['2026-12-25'],
            'skills' => ['caisse'],
            'accountId' => self::ACCOUNT_ID,
        ], $overrides));
    }

    // ---------- findAll / findActive ----------

    public function testFindAllReturnsRepositoryResult(): void
    {
        $expected = [$this->employee(), $this->employee()];
        $this->employees->expects(self::once())->method('findAll')->willReturn($expected);

        self::assertSame($expected, $this->service->findAll());
    }

    public function testFindActiveReturnsRepositoryResult(): void
    {
        $expected = [$this->employee()];
        $this->employees->expects(self::once())->method('findActive')->willReturn($expected);

        self::assertSame($expected, $this->service->findActive());
    }

    // ---------- findById ----------

    public function testFindByIdReturnsEmployee(): void
    {
        $employee = $this->employee();
        $this->employees->expects(self::once())
            ->method('findById')
            ->with(Uuid::fromString(self::EMPLOYEE_ID))
            ->willReturn($employee);

        self::assertSame($employee, $this->service->findById(self::EMPLOYEE_ID));
    }

    public function testFindByIdThrowsWhenEmployeeDoesNotExist(): void
    {
        $this->employees->method('findById')->willReturn(null);

        $this->expectException(EmployeeNotFoundException::class);

        $this->service->findById(self::EMPLOYEE_ID);
    }

    public function testFindByIdThrowsOnMalformedIdWithoutQueryingRepository(): void
    {
        $this->employees->expects(self::never())->method('findById');

        $this->expectException(EmployeeNotFoundException::class);

        $this->service->findById('not-a-uuid');
    }

    // ---------- create ----------

    public function testCreatePersistsActiveEmployeeLinkedToAccount(): void
    {
        $account = $this->account(self::ACCOUNT_ID);
        $this->accounts->expects(self::once())->method('findById')->with(Uuid::fromString(self::ACCOUNT_ID))->willReturn($account);
        $this->employees->method('existsByAccount')->willReturn(false);

        $this->employees->expects(self::once())->method('save')->with(self::callback(
            static fn (Employee $e): bool => $e->getFirstName() === 'Coreli'
                && $e->getLastName() === 'Martin'
                && $e->getDateAnciennete()?->format('Y-m-d') === '2026-07-13'
                && $e->getWeeklyHours() === 20
                && $e->getAvailableSlots() === [1, 2, 3]
                && $e->getDatesExclues() === ['2026-12-25']
                && $e->getSkills() === ['caisse']
                && $e->getAccount() === $account
        ));

        $employee = $this->service->create($this->input());

        self::assertSame($account, $employee->getAccount());
        self::assertTrue($employee->isActive());
    }

    public function testCreateThrowsWhenAccountDoesNotExist(): void
    {
        $this->accounts->expects(self::once())->method('findById')->willReturn(null);
        $this->employees->expects(self::never())->method('save');

        $this->expectException(AccountNotFoundException::class);

        $this->service->create($this->input());
    }

    public function testCreateThrowsWhenAccountAlreadyHasAnEmployee(): void
    {
        $this->accounts->expects(self::once())->method('findById')->willReturn($this->account(self::ACCOUNT_ID));
        $this->employees->expects(self::once())->method('existsByAccount')
            ->with(Uuid::fromString(self::ACCOUNT_ID))
            ->willReturn(true);
        $this->employees->expects(self::never())->method('save');

        $this->expectException(EmployeeAlreadyExistsException::class);

        $this->service->create($this->input());
    }

    // ---------- update ----------

    public function testUpdateChangesFieldsAndKeepsOriginalAccount(): void
    {
        $employee = $this->employee();
        $originalAccount = $employee->getAccount();

        $this->employees->method('findById')->willReturn($employee);
        $this->employees->expects(self::once())->method('save')->with(self::identicalTo($employee));
        $this->accounts->expects(self::never())->method('findById');

        $updated = $this->service->update(
            self::EMPLOYEE_ID,
            $this->input(['accountId' => self::OTHER_ACCOUNT_ID]),
        );

        self::assertSame('Coreli', $updated->getFirstName());
        self::assertSame(20, $updated->getWeeklyHours());
        self::assertSame([1, 2, 3], $updated->getAvailableSlots());
        self::assertSame($originalAccount, $updated->getAccount(), 'Le compte ne doit jamais changer à l\'update.');
    }

    public function testUpdateThrowsWhenEmployeeDoesNotExist(): void
    {
        $this->employees->method('findById')->willReturn(null);
        $this->employees->expects(self::never())->method('save');

        $this->expectException(EmployeeNotFoundException::class);

        $this->service->update(self::EMPLOYEE_ID, $this->input());
    }

    // ---------- deactivate / activate (soft delete) ----------

    public function testDeactivateMarksEmployeeInactiveAndSavesIt(): void
    {
        $employee = $this->employee(active: true);
        $this->employees->method('findById')->willReturn($employee);
        $this->employees->expects(self::once())->method('save')->with(self::identicalTo($employee));

        self::assertFalse($this->service->deactivate(self::EMPLOYEE_ID)->isActive());
    }

    public function testDeactivateIsIdempotent(): void
    {
        $employee = $this->employee(active: false);
        $this->employees->method('findById')->willReturn($employee);
        $this->employees->expects(self::never())->method('save');

        self::assertFalse($this->service->deactivate(self::EMPLOYEE_ID)->isActive());
    }

    public function testDeactivateThrowsWhenEmployeeDoesNotExist(): void
    {
        $this->employees->method('findById')->willReturn(null);

        $this->expectException(EmployeeNotFoundException::class);

        $this->service->deactivate(self::EMPLOYEE_ID);
    }

    public function testActivateMarksEmployeeActiveAndSavesIt(): void
    {
        $employee = $this->employee(active: false);
        $this->employees->method('findById')->willReturn($employee);
        $this->employees->expects(self::once())->method('save')->with(self::identicalTo($employee));

        self::assertTrue($this->service->activate(self::EMPLOYEE_ID)->isActive());
    }

    // ---------- delete (hard delete) ----------

    public function testDeleteRemovesEmployee(): void
    {
        $employee = $this->employee();
        $this->employees->method('findById')->willReturn($employee);
        $this->employees->expects(self::once())->method('remove')->with(self::identicalTo($employee));

        $this->service->delete(self::EMPLOYEE_ID);
    }

    public function testDeleteThrowsWhenEmployeeDoesNotExist(): void
    {
        $this->employees->method('findById')->willReturn(null);
        $this->employees->expects(self::never())->method('remove');

        $this->expectException(EmployeeNotFoundException::class);

        $this->service->delete(self::EMPLOYEE_ID);
    }

    public function testDeletePropagatesEmployeeInUse(): void
    {
        $this->employees->method('findById')->willReturn($this->employee());
        $this->employees->method('remove')->willThrowException(new EmployeeInUseException(self::EMPLOYEE_ID));

        $this->expectException(EmployeeInUseException::class);

        $this->service->delete(self::EMPLOYEE_ID);
    }
}