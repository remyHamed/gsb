<?php

namespace App\Service\Impl;

use App\Dto\Week\WeekInput;
use App\Entity\Week;
use App\Exception\InvalidWeekPeriodException;
use App\Exception\WeekAlreadyExistsException;
use App\Exception\WeekNotFoundException;
use App\Exception\WeekOverlapException;
use App\Repository\Contract\WeekRepositoryInterface;
use App\Service\Contract\WeekServiceInterface;
use Symfony\Component\Uid\Uuid;
use App\Exception\WeekInUseException;

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
        $startDate = $this->parseStartDate($input->startDate);

        $this->assertStartDateIsMonday($startDate);
        $this->assertWeekNumberInRange($input->year, $input->weekNumber);
        $this->assertWeekExistsInYear($input->year, $input->weekNumber);
        $this->assertStartDateMatchesIsoWeek($input->year, $input->weekNumber, $startDate);
        $this->assertNoDuplicate($input->year, $input->weekNumber, null);
        $this->assertNoOverlap($startDate, null);

        $week = new Week();
        $week->setYear($input->year);
        $week->setWeekNumber($input->weekNumber);
        $week->setStartDate($startDate);

        $this->weeks->save($week);

        return $week;
    }

    public function update(string $id, WeekInput $input): Week
    {
        $week = $this->findById($id);
        $startDate = $this->parseStartDate($input->startDate);

        $this->assertPeriodChangeAllowed($week, $input->year, $input->weekNumber, $startDate);
        $this->assertStartDateIsMonday($startDate);
        $this->assertWeekNumberInRange($input->year, $input->weekNumber);
        $this->assertWeekExistsInYear($input->year, $input->weekNumber);
        $this->assertStartDateMatchesIsoWeek($input->year, $input->weekNumber, $startDate);
        $this->assertNoDuplicate($input->year, $input->weekNumber, $week->getId());
        $this->assertNoOverlap($startDate, $week->getId());

        $week->setYear($input->year);
        $week->setWeekNumber($input->weekNumber);
        $week->setStartDate($startDate);

        $this->weeks->save($week);

        return $week;
    }

    public function delete(string $id): void
    {
        $this->weeks->remove($this->findById($id));
    }

    private function parseStartDate(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw InvalidWeekPeriodException::invalidDate($value);
        }

        return $date;
    }

    private function assertStartDateIsMonday(\DateTimeImmutable $startDate): void
    {
        if ($startDate->format('N') !== '1') {
            throw InvalidWeekPeriodException::startDateNotMonday($startDate->format('Y-m-d'));
        }
    }

    private function assertWeekNumberInRange(int $year, int $weekNumber): void
    {
        if ($weekNumber < 1 || $weekNumber > 53) {
            throw InvalidWeekPeriodException::invalidWeekNumber($year, $weekNumber);
        }
    }

    private function assertWeekExistsInYear(int $year, int $weekNumber): void
    {
        // setISODate déborde sur l'année suivante si la semaine n'existe pas (ex. S53 de 2027)
        $monday = $this->isoMonday($year, $weekNumber);

        if ((int) $monday->format('o') !== $year || (int) $monday->format('W') !== $weekNumber) {
            throw InvalidWeekPeriodException::invalidWeekNumber($year, $weekNumber);
        }
    }

    private function assertStartDateMatchesIsoWeek(int $year, int $weekNumber, \DateTimeImmutable $startDate): void
    {
        $expected = $this->isoMonday($year, $weekNumber);

        if ($expected->format('Y-m-d') !== $startDate->format('Y-m-d')) {
            throw InvalidWeekPeriodException::startDateMismatch(
                $year,
                $weekNumber,
                $expected->format('Y-m-d'),
                $startDate->format('Y-m-d'),
            );
        }
    }

    private function assertNoDuplicate(int $year, int $weekNumber, ?Uuid $excludeId): void
    {
        if ($this->weeks->existsDuplicate($year, $weekNumber, $excludeId)) {
            throw new WeekAlreadyExistsException($year, $weekNumber);
        }
    }

    private function assertNoOverlap(\DateTimeImmutable $startDate, ?Uuid $excludeId): void
    {
        $endDate = $startDate->modify('+7 days'); // fin exclusive

        if ($this->weeks->overlapsPeriod($startDate, $endDate, $excludeId)) {
            throw new WeekOverlapException($startDate, $endDate);
        }
    }

    private function isoMonday(int $year, int $weekNumber): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('today'))
            ->setISODate($year, $weekNumber, 1)
            ->setTime(0, 0);
    }

    private function assertPeriodChangeAllowed(
        Week $week,
        int $year,
        int $weekNumber,
        \DateTimeImmutable $startDate,
    ): void {
        if ($week->getShifts()->isEmpty()) {
            return;
        }

        $unchanged = $week->getYear() === $year
            && $week->getWeekNumber() === $weekNumber
            && $week->getStartDate()?->format('Y-m-d') === $startDate->format('Y-m-d');

        if (!$unchanged) {
            throw new WeekInUseException();
        }
    }
}