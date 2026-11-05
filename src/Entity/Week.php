<?php
namespace App\Entity;

use App\Repository\WeekRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: WeekRepository::class)]
#[ORM\Table(name: 'week')]
#[ORM\UniqueConstraint(columns: ['year', 'week_number'])]
class Week
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;

    #[ORM\Column]
    private int $year;

    #[ORM\Column(name: 'week_number')]
    private int $weekNumber;

    #[ORM\Column(name: 'start_date', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    #[ORM\OneToMany(mappedBy: 'week', targetEntity: Shift::class, cascade: ['remove'])]
    private Collection $shifts;

    public function __construct()
    {
        $this->shifts = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;

        return $this;
    }

    public function getWeekNumber(): ?int
    {
        return $this->weekNumber;
    }

    public function setWeekNumber(int $weekNumber): static
    {
        $this->weekNumber = $weekNumber;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * @return Collection<int, Shift>
     */
    public function getShifts(): Collection
    {
        return $this->shifts;
    }

    public function addShift(Shift $shift): static
    {
        if (!$this->shifts->contains($shift)) {
            $this->shifts->add($shift);
            $shift->setWeek($this);
        }

        return $this;
    }

    public function removeShift(Shift $shift): static
    {
        if ($this->shifts->removeElement($shift)) {
            // set the owning side to null (unless already changed)
            if ($shift->getWeek() === $this) {
                $shift->setWeek(null);
            }
        }

        return $this;
    }
}