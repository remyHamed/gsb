<?php

namespace App\Dto\Scheduling;

use Symfony\Component\Validator\Constraints as Assert;

final class AssignEmployeeInput
{
    #[Assert\NotBlank]
    #[Assert\Uuid]
    public string $employeeId = '';
}