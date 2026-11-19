<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(422)]
final class InvalidWeekPeriodException extends \DomainException
{
    public static function invalidDate(string $value): self
    {
        return new self(sprintf('Date de début invalide "%s" (format attendu : Y-m-d).', $value));
    }

    public static function startDateNotMonday(string $given): self
    {
        return new self(sprintf('La date de début %s n\'est pas un lundi.', $given));
    }

    public static function invalidWeekNumber(int $year, int $week): self
    {
        return new self(sprintf('La semaine %d n\'existe pas en %d.', $week, $year));
    }

    public static function startDateMismatch(int $year, int $week, string $expected, string $given): self
    {
        return new self(sprintf(
            'La semaine %d de %d commence le %s, pas le %s.',
            $week,
            $year,
            $expected,
            $given,
        ));
    }
}