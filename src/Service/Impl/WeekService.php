<?php

namespace App\Service\Impl;

use App\Dto\WeekInput;
use App\Entity\Week;
use App\Exception\WeekAlreadyExistsException;
use App\Exception\WeekNotFoundException;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Contract\WeekServiceInterface;
use Symfony\Component\Uid\Uuid;

final class WeekService implements WeekServiceInterface
{
    public function __construct(private readonly WeekRepositoryInterface $weeks)
    {
    }

    public function findAll(): array
    {
        return $this->weeks->findAll();
    }

    public function findById(string $id): Week
    {
        $week = Uuid::isValid($id) ? $this->weeks->findById(Uuid::fromString($id)) : null;

        return $week ?? throw new WeekNotFoundException($id);
    }

    public function create(WeekInput $input): Week
    {
        $this->assertNoDuplicate($input->year, $input->weekNumber, null);

        $week = new Week();
        $week->setYear($input->year);
        $week->setWeekNumber($input->weekNumber);
        $week->setStartDate(new \DateTimeImmutable($input->startDate));

        $this->weeks->save($week);

        return $week;
    }

    public function update(string $id, WeekInput $input): Week
    {
        $week = $this->findById($id);
        $this->assertNoDuplicate($input->year, $input->weekNumber, $week->getId());

        $week->setYear($input->year);
        $week->setWeekNumber($input->weekNumber);
        $week->setStartDate(new \DateTimeImmutable($input->startDate));

        $this->weeks->save($week);

        return $week;
    }

    public function delete(string $id): void
    {
        $this->weeks->remove($this->findById($id));
    }

    private function assertNoDuplicate(int $year, int $weekNumber, ?Uuid $excludeId): void
    {
        if ($this->weeks->existsDuplicate($year, $weekNumber, $excludeId)) {
            throw new WeekAlreadyExistsException($year, $weekNumber);
        }
    }
}
