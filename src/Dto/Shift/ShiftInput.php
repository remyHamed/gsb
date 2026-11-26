<?php

namespace App\Dto\Shift;

final readonly class ShiftInput
{
    public function __construct(
        public string $weekId,
        public string $date,
        public string $startTime,
        public string $endTime,
        public array $skillsRequired = [],
    ) {
    }
}