<?php
namespace App\Entity;

use App\Repository\Impl\EmployeeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
#[ORM\UniqueConstraint(name: 'uniq_employee_identity', columns: ['account_id', 'first_name', 'last_name', 'date_anciennete'])]
#[ORM\Table(name: 'employees')]
class Employee
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(name: 'first_name')]
    private string $firstName;

    #[ORM\Column(name: 'last_name')]
    private string $lastName;

    #[ORM\Column(name: 'date_anciennete', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $dateAnciennete;

    #[ORM\Column(name: 'weekly_hours')]
    private int $weeklyHours;

    #[ORM\Column(name: 'available_slots', type: Types::JSON)]
    private array $availableSlots = [];

    #[ORM\Column(name: 'dates_exclues', type: Types::JSON)]
    private array $datesExclues = [];

    #[ORM\Column(type: Types::JSON)]
    private array $skills = [];

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToOne(inversedBy: 'employee', targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'account_id', nullable: false, unique: true)]
    private Account $account;

public function __construct(
    Account $account,
    string $firstName,
    string $lastName,
    \DateTimeImmutable $dateAnciennete,
    int $weeklyHours
) {
    $this->account = $account;
    $this->firstName = $firstName;
    $this->lastName = $lastName;
    $this->dateAnciennete = $dateAnciennete;
    $this->weeklyHours = $weeklyHours;
    $this->createdAt = new \DateTimeImmutable();
}

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getDateAnciennete(): ?\DateTimeImmutable
    {
        return $this->dateAnciennete;
    }

    public function setDateAnciennete(\DateTimeImmutable $dateAnciennete): static
    {
        $this->dateAnciennete = $dateAnciennete;

        return $this;
    }

    public function getWeeklyHours(): ?int
    {
        return $this->weeklyHours;
    }

    public function setWeeklyHours(int $weeklyHours): static
    {
        $this->weeklyHours = $weeklyHours;

        return $this;
    }

    public function getAvailableSlots(): array
    {
        return $this->availableSlots;
    }

    public function setAvailableSlots(array $availableSlots): static
    {
        $this->availableSlots = $availableSlots;

        return $this;
    }

    public function getDatesExclues(): array
    {
        return $this->datesExclues;
    }

    public function setDatesExclues(array $datesExclues): static
    {
        $this->datesExclues = $datesExclues;

        return $this;
    }

    public function getSkills(): array
    {
        return $this->skills;
    }

    public function setSkills(array $skills): static
    {
        $this->skills = $skills;

        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(Account $account): static
    {
        $this->account = $account;

        return $this;
    }

    public function update(
        string $firstName,
        string $lastName,
        \DateTimeImmutable $dateAnciennete,
        int $weeklyHours,
        array $availableSlots,
        array $datesExclues,
        array $skills
    ): self {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->dateAnciennete = $dateAnciennete;
        $this->weeklyHours = $weeklyHours;
        $this->availableSlots = $availableSlots;
        $this->datesExclues = $datesExclues;
        $this->skills = $skills;

        return $this;
    }

    public function deactivate(): self
    {
        $this->isActive = false;

        return $this;
    }

    public function activate(): self
    {
        $this->isActive = true;

        return $this;
    }

public function hasAllSkills(array $requiredSkills): bool
    {
        return array_diff($requiredSkills, $this->skills) === [];
    }

    public function isAvailableFor(\DateTimeImmutable $date, int $slotNumber): bool
    {
        if (in_array($date->format('Y-m-d'), $this->datesExclues, true)) {
            return false;
        }

        return in_array($slotNumber, $this->availableSlots, true);
    }

    public function weeklyCapacityInMinutes(): int
    {
        return $this->weeklyHours * 60;
    }
}