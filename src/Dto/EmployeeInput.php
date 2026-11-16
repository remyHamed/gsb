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

        #[Assert\NotBlank]
        #[Assert\DateTime(format: \DateTimeInterface::ATOM)]
        public readonly string $dateAnciennete = '',

        #[Assert\PositiveOrZero]
        public readonly int $weeklyHours = 0,

        /** @var int[] */
        public readonly array $joursDisponibles = [],

        /** @var string[] */
        public readonly array $datesExclues = [],

        /** @var string[] */
        public readonly array $skills = [],

        #[Assert\NotBlank]
        #[Assert\Uuid]
        public readonly string $accountId = '',
    ) {
    }
}