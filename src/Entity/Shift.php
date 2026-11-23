<?php
namespace App\Entity;

use App\Repository\Impl\ShiftRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ShiftRepository::class)]
#[ORM\Table(name: 'shift')]
class Shift
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $date;

    #[ORM\Column(name: 'start_time', length: 5)]
    private string $startTime;

    #[ORM\Column(name: 'end_time', length: 5)]
    private string $endTime;

    #[ORM\Column(name: 'skills_required', type: Types::JSON)]
    private array $skillsRequired = [];

    #[ORM\ManyToOne(inversedBy: 'shifts')]
    #[ORM\JoinColumn(name: 'week_id', nullable: false, onDelete: 'CASCADE')]
    private Week $week;

    #[ORM\ManyToMany(targetEntity: Employee::class)]
    #[ORM\JoinTable(name: 'shift_employee')]
    private Collection $assignedEmployees;

    public function __construct()
    {
        $this->assignedEmployees = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): static
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): static
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getSkillsRequired(): array
    {
        return $this->skillsRequired;
    }

    public function setSkillsRequired(array $skillsRequired): static
    {
        $this->skillsRequired = $skillsRequired;

        return $this;
    }

    public function getWeek(): ?Week
    {
        return $this->week;
    }

    public function setWeek(?Week $week): static
    {
        $this->week = $week;

        return $this;
    }

    /**
     * @return Collection<int, Employee>
     */
    public function getAssignedEmployees(): Collection
    {
        return $this->assignedEmployees;
    }

    public function addAssignedEmployee(Employee $assignedEmployee): static
    {
        if (!$this->assignedEmployees->contains($assignedEmployee)) {
            $this->assignedEmployees->add($assignedEmployee);
        }

        return $this;
    }

    public function removeAssignedEmployee(Employee $assignedEmployee): static
    {
        $this->assignedEmployees->removeElement($assignedEmployee);

        return $this;
    }
}