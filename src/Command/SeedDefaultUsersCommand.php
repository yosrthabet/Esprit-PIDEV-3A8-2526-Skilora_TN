<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-default-users',
    description: 'Creates or updates default admin and user accounts (bcrypt passwords).',
)]
final class SeedDefaultUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite password and role if the username already exists.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = $input->getOption('force');

        $accounts = [
            [
                'username' => 'admin',
                'plainPassword' => 'admin123',
                'role' => 'ADMIN',
                'fullName' => 'admin',
                'email' => 'admin@skilora.local',
            ],
            [
                'username' => 'user',
                'plainPassword' => 'user123',
                'role' => 'USER',
                'fullName' => 'user',
                'email' => 'user@skilora.local',
            ],
        ];

        foreach ($accounts as $row) {
            $user = $this->userRepository->findOneBy(['username' => $row['username']]);
            if ($user === null) {
                $user = new User();
                $user->setUsername($row['username']);
                $this->entityManager->persist($user);
                $io->text(sprintf('Creating user <info>%s</info>', $row['username']));
            } elseif (!$force) {
                $io->warning(sprintf('Username "%s" already exists — skipped (use --force to update).', $row['username']));

                continue;
            } else {
                $io->text(sprintf('Updating user <info>%s</info>', $row['username']));
            }

            $user->setEmail($row['email']);
            $user->setFullName($row['fullName']);
            $user->setRole($row['role']);
            $user->setActive(true);
            $user->setVerified(true);
            $user->setPassword($this->passwordHasher->hashPassword($user, $row['plainPassword']));
        }

        $this->entityManager->flush();

        $io->success('Done. Login with username + password (not email): admin / admin123, user / user123');

        return Command::SUCCESS;
    }
}
