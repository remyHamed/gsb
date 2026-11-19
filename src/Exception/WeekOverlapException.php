<?php

namespace App\Exception;

final class WeekOverlapException extends \DomainException
{
    public function __construct(\DateTimeImmutable $start, \DateTimeImmutable $end)
    {
        parent::__construct(sprintf(
            'La période du %s au %s chevauche une semaine existante.',
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
        ));
    }
}