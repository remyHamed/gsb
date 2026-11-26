<?php

namespace App\Dto\Employee;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class EmployeeInput
{
    /**
     * @param int[]    $joursDisponibles
     * @param string[] $datesExclues
     * @param string[] $skills
     */
    public function __construct(
        #[Assert\NotBlank(message: "L'identifiant du compte est obligatoire.")]
        #[Assert\Uuid(message: "L'identifiant du compte doit être un UUID valide.")]
        public string $accountId,

        #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
        #[Assert\Length(min: 2, max: 100)]
        public string $firstName,

        #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
        #[Assert\Length(min: 2, max: 100)]
        public string $lastName,

        #[Assert\NotBlank(message: "La date d'ancienneté est obligatoire.")]
        #[Assert\Date(message: 'La date doit être au format YYYY-MM-DD.')]
        public string $dateAnciennete,

        #[Assert\NotNull(message: 'Le volume horaire hebdomadaire est obligatoire.')]
        #[Assert\Range(min: 0, max: 60, notInRangeMessage: 'Les heures doivent être comprises entre {{ min }} et {{ max }}.')]
        public int $weeklyHours,

        #[Assert\NotNull]
        #[Assert\All([
            new Assert\Type('integer'),
            new Assert\Range(min: 1, max: 7),
        ])]
        public array $joursDisponibles = [],

        #[Assert\NotNull]
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\Date(),
        ])]
        public array $datesExclues = [],

        #[Assert\NotNull]
        #[Assert\All([
            new Assert\Type('string'),
            new Assert\NotBlank(),
            new Assert\Length(max: 50),
        ])]
        public array $skills = [],
    ) {
    }
}