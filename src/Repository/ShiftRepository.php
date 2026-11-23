<?php

namespace App\Repository;

use App\Entity\Shift;
use App\Repository\Contract\ShiftRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/** @extends ServiceEntityRepository<Shift> */
class ShiftRepository extends ServiceEntityRepository implements ShiftRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shift::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['date' => 'ASC', 'startTime' => 'ASC']);
    }

    public function findByWeek(Uuid $weekId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.week = :weekId')
            ->setParameter('weekId', $weekId, UuidType::NAME)
            ->orderBy('s.date', 'ASC')
            ->addOrderBy('s.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findById(Uuid $id): ?Shift
    {
        return $this->find($id);
    }
}