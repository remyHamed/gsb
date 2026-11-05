<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\Employee;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'app:import-employees',
    description: 'Importe des employés depuis un JSON et les rattache aux comptes existants (prenom.nom@domaine)',
)]
final class ImportEmployeesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::REQUIRED, 'Chemin du fichier JSON')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule sans rien écrire en base')
            ->addOption('domain', null, InputOption::VALUE_REQUIRED, "Domaine des emails des comptes", 'entreprise.fr')
            ->addOption('create-accounts', null, InputOption::VALUE_NONE, 'Crée les comptes manquants (nécessite --password)')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Mot de passe initial des comptes créés');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getArgument('file');
        $domain = $input->getOption('domain');
        $dryRun = $input->getOption('dry-run');
        $createAccounts = $input->getOption('create-accounts');
        $password = $input->getOption('password');

        if ($createAccounts && !$password) {
            $io->error('--create-accounts nécessite --password');

            return Command::FAILURE;
        }

        if (!is_file($file)) {
            $io->error("Fichier introuvable : $file");

            return Command::FAILURE;
        }

        try {
            $rows = json_decode(file_get_contents($file), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $io->error('JSON invalide : '.$e->getMessage());

            return Command::FAILURE;
        }

        $slugger = new AsciiSlugger();
        $accounts = $this->em->getRepository(Account::class);
        $created = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $name = $row['firstName'].' '.$row['lastName'];
            $email = sprintf(
                '%s.%s@%s',
                $slugger->slug($row['firstName'])->lower(),
                $slugger->slug($row['lastName'])->lower(),
                $domain,
            );

            $account = $accounts->findOneBy(['email' => $email]);
            if ($account === null && $createAccounts) {
                $account = (new Account())
                    ->setEmail($email)
                    ->setPasswordHash(password_hash($password, \PASSWORD_DEFAULT))
                    ->setRoles(['EMPLOYEE']);
                $this->em->persist($account);
                $io->writeln("<info>+</info> compte $email");
            } elseif ($account === null) {
                $io->warning("$name : aucun compte pour $email, ignoré (utilise --create-accounts)");
                ++$skipped;
                continue;
            } elseif ($account->getEmployee() !== null) {
                $io->note("$name : employé déjà existant, ignoré");
                ++$skipped;
                continue;
            }

            $employee = (new Employee())
                ->setAccount($account)
                ->setFirstName($row['firstName'])
                ->setLastName($row['lastName'])
                ->setDateAnciennete(new \DateTimeImmutable($row['dateAnciennete']))
                ->setWeeklyHours((int) $row['weeklyHours'])
                // Ancien format : jours de la semaine (0 = dimanche ... 6 = samedi)
                ->setAvailableSlots($row['joursDisponibles'] ?? [])
                ->setDatesExclues($row['datesExclues'] ?? [])
                ->setSkills($row['skills'] ?? [])
                ->setIsActive($row['isActive'] ?? true);

            $this->em->persist($employee);
            ++$created;
            $io->writeln("<info>+</info> $name ($email)");
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        $io->success(sprintf('%s%d créé(s), %d ignoré(s).', $dryRun ? '[DRY-RUN] ' : '', $created, $skipped));

        return Command::SUCCESS;
    }
}