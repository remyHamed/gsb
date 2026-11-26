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

    public function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    public function endMinutes(string $endTime, int $startMinutes): int
    {
        $end = $this->toMinutes($endTime);

        return ($end === 0 && $startMinutes > 0) ? 1440 : $end;
    }


    public function durationInMinutes(): int
    {
        return max(0, $this->endInMinutes() - $this->startInMinutes());
    }

    public function resolveSlotNumber(): ?int
    {
        $dayOfWeek = (int) $this->date->format('N');
        $base = ($dayOfWeek - 1) * 2;
        $start = $this->startInMinutes();
        $end = $this->endInMinutes();

        if ($start >= 630 && $end <= 960) { // 10:30 (630 min) -> 16:00 (960 min)
            return $base + 1;
        }

        if ($start >= 1050 && $end <= 1440) { // 17:30 (1050 min) -> 00:00 (1440 min)
            return $base + 2;
        }

        return null;
    }

    public function overlapsWith(self $other): bool
    {
        if ($this->date->format('Y-m-d') !== $other->date->format('Y-m-d')) {
            return false;
        }

        return $this->startInMinutes() < $other->endInMinutes()
            && $this->endInMinutes() > $other->startInMinutes();
    }

    private function startInMinutes(): int
    {
        return self::parseTimeToMinutes($this->startTime);
    }

    private function endInMinutes(): int
    {
        $end = self::parseTimeToMinutes($this->endTime);

        return ($end === 0 && $this->startInMinutes() > 0) ? 1440 : $end;
    }

    private static function parseTimeToMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }
}