<?php

namespace App\Dto\Shift;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ShiftInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Uuid]
        public string $weekId = '',

        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date = '',

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^([01]\d|2[0-3]):[0-5]\d$/')]
        public string $startTime = '',

        #[Assert\NotBlank]
        #[Assert\Regex(pattern: '/^([01]\d|2[0-3]):[0-5]\d$/')]
        public string $endTime = '',

        #[Assert\Type('array')]
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\NotBlank(),
        ])]
        public array $skillsRequired = [],
    ) {
    }
}