<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(409)]
final class WeekAlreadyExistsException extends \DomainException
{
    public function __construct(int $year, int $weekNumber, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf('La semaine %d-%02d existe déjà.', $year, $weekNumber),
            0,
            $previous
        );
    }
}
