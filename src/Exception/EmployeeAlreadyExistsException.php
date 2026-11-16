<?php
namespace App\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(409)]
final class EmployeeAlreadyExistsException extends \DomainException
{
    public function __construct(string $accountId, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('An employee already exists for account "%s".', $accountId), 0, $previous);
    }
}