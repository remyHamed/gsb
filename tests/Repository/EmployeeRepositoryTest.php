<?php

namespace App\Tests\Repository;

use App\Entity\Account;
use App\Entity\Employee;
use App\Exception\EmployeeAlreadyExistsException;
use App\Repository\Contract\EmployeeRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\Uuid;

final class EmployeeRepositoryTest extends KernelTestCase
{
    private EmployeeRepositoryInterface $repository;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = static::getContainer()->get(EmployeeRepositoryInterface::class);
        $this->em = static::getContainer()->get('doctrine')->getManager();

        $this->em->createQuery('DELETE FROM ' . Employee::class)->execute();
        $this->em->createQuery('DELETE FROM ' . Account::class)->execute();
    }

    // ---------- Fixtures ----------

    private function account(string $email): Account
    {
        $account = (new Account())->setEmail($email)->setPasswordHash('x');
        $this->em->persist($account);
        $this->em->flush();

        return $account;
    }

    private function employee(Account $account, string $firstName, string $lastName, bool $active = true): Employee
    {
        return (new Employee())
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setDateAnciennete(new \DateTimeImmutable('2024-01-01'))
            ->setWeeklyHours(35)
            ->setIsActive($active)
            ->setAccount($account);
    }

    // ---------- Tests ----------

    public function testExistsByAccountIsTrueOnlyForAnAccountWithAnEmployee(): void
    {
        $withEmployee = $this->account('alice@example.com');
        $without = $this->account('bob@example.com');
        $this->repository->save($this->employee($withEmployee, 'Alice', 'Martin'));

        self::assertTrue($this->repository->existsByAccount($withEmployee->getId()));
        self::assertFalse($this->repository->existsByAccount($without->getId()));
    }

    public function testSaveRejectsASecondEmployeeForTheSameAccount(): void
    {
        $account = $this->account('alice@example.com');
        $this->repository->save($this->employee($account, 'Alice', 'Martin'));

        $this->expectException(EmployeeAlreadyExistsException::class);

        $this->repository->save($this->employee($account, 'Alicia', 'Martin'));
    }

    public function testFindByIdReturnsNullForUnknownId(): void
    {
        self::assertNull($this->repository->findById(Uuid::v7()));
    }

    public function testFindActiveReturnsOnlyActiveEmployeesSortedByLastThenFirstName(): void
    {
        $this->repository->save($this->employee($this->account('z@example.com'), 'Zoe', 'Martin'));
        $this->repository->save($this->employee($this->account('a@example.com'), 'Alice', 'Martin'));
        $this->repository->save($this->employee($this->account('b@example.com'), 'Bob', 'Dupont', active: false));

        $names = array_map(
            static fn (Employee $e): string => $e->getFirstName(),
            $this->repository->findActive(),
        );

        self::assertSame(['Alice', 'Zoe'], $names);
    }

    public function testRemoveDeletesTheEmployeeButNotItsAccount(): void
    {
        $account = $this->account('alice@example.com');
        $employee = $this->employee($account, 'Alice', 'Martin');
        $this->repository->save($employee);
        $employeeId = $employee->getId();
        $accountId = $account->getId();

        $this->repository->remove($employee);
        $this->em->clear(); // on relit la base, pas l'identity map

        self::assertNull($this->repository->findById($employeeId));
        self::assertNotNull($this->em->find(Account::class, $accountId));
    }

   
}