<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:update-passwords',
    description: 'Update all user passwords with bcrypt hashing',
)]
class UpdateUserPasswordsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        $users = $userRepository->findAll();

        $passwordMap = [
            'admin' => 'admin123',
            'user' => 'user123',
            'employer' => 'emp123',
        ];

        foreach ($users as $user) {
            if (isset($passwordMap[$user->getUsername()])) {
                $plainPassword = $passwordMap[$user->getUsername()];
                $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
                $output->writeln("✓ Updated password for user: <info>{$user->getUsername()}</info>");
            }
        }

        $this->entityManager->flush();
        $output->writeln("\n<fg=green>All passwords updated successfully!</>");

        return Command::SUCCESS;
    }
}
