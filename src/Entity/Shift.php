<?php
namespace App\Entity;

use App\Repository\ShiftRepository;
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
}