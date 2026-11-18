<?php
namespace App\Repository;

use App\Entity\Week;
use App\Exception\WeekAlreadyExistsException;
use App\Repository\Contract\WeekRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

/** @extends ServiceEntityRepository<Week> */
class WeekRepository extends ServiceEntityRepository implements WeekRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Week::class);
    }

    public function findById(Uuid $id): ?Week
    {
        return $this->find($id);
    }

    public function existsDuplicate(int $year, int $weekNumber, ?Uuid $excludeId): bool
    {
        $qb = $this->createQueryBuilder('w')
            ->select('COUNT(w.id)')
            ->andWhere('w.year = :year')
            ->andWhere('w.weekNumber = :weekNumber')
            ->setParameter('year', $year)
            ->setParameter('weekNumber', $weekNumber);

        if ($excludeId !== null) {
            $qb->andWhere('w.id != :excludeId')
                ->setParameter('excludeId', $excludeId, UuidType::NAME);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function save(Week $week): void
    {
        $em = $this->getEntityManager();
        $em->persist($week);

        try {
            $em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new WeekAlreadyExistsException($week->getYear() ?? 0, $week->getWeekNumber() ?? 0, $e);
        }
    }

    public function remove(Week $week): void
    {
        $em = $this->getEntityManager();
        $em->remove($week);
        $em->flush();
    }
}