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
        return $this->findBy(['isActive' => true], ['lastName' => 'ASC', 'firstName' => 'ASC']);
    }

    public function findById(Uuid $id): ?Employee
    {
        return $this->find($id);
    }

    public function existsDuplicate(Uuid $accountId, string $firstName, string $lastName, \DateTimeImmutable $seniorityDate, ?Uuid $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.account = :account')
            ->andWhere('LOWER(e.firstName) = LOWER(:firstName)')
            ->andWhere('LOWER(e.lastName) = LOWER(:lastName)')
            ->andWhere('e.dateAnciennete = :seniority')
            ->setParameter('account', $accountId, UuidType::NAME)
            ->setParameter('firstName', $firstName)
            ->setParameter('lastName', $lastName)
            ->setParameter('seniority', $seniorityDate, Types::DATE_IMMUTABLE);

        if ($excludeId !== null) {
            $qb->andWhere('e.id != :excluded')->setParameter('excluded', $excludeId, UuidType::NAME);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function save(Employee $employee): void
    {
        $em = $this->getEntityManager();
        $em->persist($employee);

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new EmployeeAlreadyExistsException('Employee already exists.', 0, $e);
        }
    }
}