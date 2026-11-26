<?php

namespace App\Dto\Week;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class WeekInput
{
    public function __construct(
        #[Assert\NotNull(message: "L'année est obligatoire.")]
        #[Assert\Range(min: 2000, max: 2100, notInRangeMessage: "L'année doit être comprise entre {{ min }} et {{ max }}.")]
        public int $year,

        #[Assert\NotNull(message: 'Le numéro de semaine est obligatoire.')]
        #[Assert\Range(min: 1, max: 53, notInRangeMessage: 'Le numéro de semaine doit être compris entre {{ min }} et {{ max }}.')]
        public int $weekNumber,

        #[Assert\NotBlank(message: 'La date de début est obligatoire.')]
        #[Assert\Date(message: 'La date doit être au format YYYY-MM-DD.')]
        public string $startDate,
    ) {
    }
}
