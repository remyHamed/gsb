<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(409)]
final class WeekInUseException extends \DomainException
{
    public function __construct()
    {
        parent::__construct('Cannot change the period of a week that already has shifts.');
    }
}