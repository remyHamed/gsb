$ErrorActionPreference = 'Stop'
$utf8 = New-Object System.Text.UTF8Encoding($false)   # UTF-8 sans BOM (sinon PHP casse)

function Write-File($path, $content) {
    $full = Join-Path (Get-Location) $path
    New-Item -ItemType Directory -Force -Path (Split-Path $full) | Out-Null
    [System.IO.File]::WriteAllText($full, $content, $utf8)
}

# 1. Dépendances
composer require symfony/orm-pack symfony/uid symfony/form symfony/validator symfony/twig-bundle symfony/security-csrf
composer require --dev symfony/maker-bundle

# 2. Dossiers (miroir de ton architecture)
'Enum','Entity','Repository','Service','Controller','Dto','Mapper','EventSubscriber','Exception' | ForEach-Object {
    New-Item -ItemType Directory -Force -Path "src/$_" | Out-Null
}

# 3. Enum
Write-File 'src/Enum/Role.php' @'
<?php
namespace App\Enum;

enum Role: string
{
    case EMPLOYEE = 'EMPLOYEE';
    case MANAGER  = 'MANAGER';
    case ADMIN    = 'ADMIN';
}
'@

# 4. Entités (propriétés uniquement, les getters/setters sont générés après)
$idBlock = @'
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    private ?Uuid $id = null;
'@

Write-File 'src/Entity/Account.php' @"
<?php
namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\Table(name: 'accounts')]
class Account
{
$idBlock

    #[ORM\Column(unique: true)]
    private string `$email;

    #[ORM\Column(name: 'password_hash')]
    private string `$passwordHash;

    #[ORM\Column(type: Types::JSON)]
    private array `$roles = ['EMPLOYEE'];

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool `$isActive = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable `$createdAt;

    #[ORM\OneToOne(mappedBy: 'account', targetEntity: Employee::class)]
    private ?Employee `$employee = null;

    public function __construct()
    {
        `$this->createdAt = new \DateTimeImmutable();
    }
}
"@

Write-File 'src/Entity/Employee.php' @"
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
$idBlock

    #[ORM\Column(name: 'first_name')]
    private string `$firstName;

    #[ORM\Column(name: 'last_name')]
    private string `$lastName;

    #[ORM\Column(name: 'date_anciennete', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable `$dateAnciennete;

    #[ORM\Column(name: 'weekly_hours')]
    private int `$weeklyHours;

    #[ORM\Column(name: 'available_slots', type: Types::JSON)]
    private array `$availableSlots = [];

    #[ORM\Column(name: 'dates_exclues', type: Types::JSON)]
    private array `$datesExclues = [];

    #[ORM\Column(type: Types::JSON)]
    private array `$skills = [];

    #[ORM\Column(name: 'is_active', options: ['default' => true])]
    private bool `$isActive = true;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable `$createdAt;

    #[ORM\OneToOne(inversedBy: 'employee', targetEntity: Account::class)]
    #[ORM\JoinColumn(name: 'account_id', nullable: false, unique: true)]
    private Account `$account;

    public function __construct()
    {
        `$this->createdAt = new \DateTimeImmutable();
    }
}
"@

Write-File 'src/Entity/Week.php' @"
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
$idBlock

    #[ORM\Column]
    private int `$year;

    #[ORM\Column(name: 'week_number')]
    private int `$weekNumber;

    #[ORM\Column(name: 'start_date', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable `$startDate;

    #[ORM\OneToMany(mappedBy: 'week', targetEntity: Shift::class, cascade: ['remove'])]
    private Collection `$shifts;

    public function __construct()
    {
        `$this->shifts = new ArrayCollection();
    }
}
"@

Write-File 'src/Entity/Shift.php' @"
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
$idBlock

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable `$date;

    #[ORM\Column(name: 'start_time', length: 5)]
    private string `$startTime;

    #[ORM\Column(name: 'end_time', length: 5)]
    private string `$endTime;

    #[ORM\Column(name: 'skills_required', type: Types::JSON)]
    private array `$skillsRequired = [];

    #[ORM\ManyToOne(inversedBy: 'shifts')]
    #[ORM\JoinColumn(name: 'week_id', nullable: false, onDelete: 'CASCADE')]
    private Week `$week;

    #[ORM\ManyToMany(targetEntity: Employee::class)]
    #[ORM\JoinTable(name: 'shift_employee')]
    private Collection `$assignedEmployees;

    public function __construct()
    {
        `$this->assignedEmployees = new ArrayCollection();
    }
}
"@

# 5. Repositories
foreach ($e in 'Account','Employee','Week','Shift') {
    Write-File "src/Repository/${e}Repository.php" @"
<?php
namespace App\Repository;

use App\Entity\$e;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<$e> */
class ${e}Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry `$registry)
    {
        parent::__construct(`$registry, $e::class);
    }
}
"@
}

# 6. Getters/setters
php bin/console make:entity --regenerate --overwrite "App\Entity" --no-interaction

# 7. Base de données
php bin/console doctrine:database:create --if-not-exists
php bin/console make:migration --no-interaction
php bin/console doctrine:migrations:migrate --no-interaction

# 8. CRUD (controller + FormType + templates Twig)
foreach ($e in 'Account','Employee','Week','Shift') {
    php bin/console make:crud $e --no-interaction
}

Write-Host "Terminé." -ForegroundColor Green