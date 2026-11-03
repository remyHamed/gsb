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
}