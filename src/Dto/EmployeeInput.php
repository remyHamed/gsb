<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class EmployeeInput
{
    public function __construct(
        #[Assert\NotBlank]
        public readonly string $firstName = '',
        #[Assert\NotBlank]
        public readonly string $lastName = '',
    ) {
    }
}