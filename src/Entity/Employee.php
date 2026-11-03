<?php
namespace App\Entity;

use App\Repository\EmployeeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: EmployeeRepository::class)]
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

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
}