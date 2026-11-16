<?php
namespace App\Exception;

use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(409)]
final class EmployeeInUseException extends \DomainException
{
    public function __construct(string $id, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Employee "%s" is referenced by shifts: deactivate it instead.', $id), 0, $previous);
    }
}