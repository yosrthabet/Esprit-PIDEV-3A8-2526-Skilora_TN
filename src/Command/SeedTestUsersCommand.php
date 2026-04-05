<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-test-users',
    description: 'Crée les comptes de démo (admin, user, employer) s’ils n’existent pas encore.',
)]
class SeedTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $accounts = [
            ['admin', 'admin123', 'ADMIN', 'Administrateur'],
            ['user', 'user123', 'USER', 'Utilisateur test'],
            ['employer', 'emp123', 'EMPLOYER', 'Employeur test'],
        ];

        $repo = $this->em->getRepository(User::class);
        foreach ($accounts as [$username, $plain, $role, $fullName]) {
            if ($repo->findOneBy(['username' => $username])) {
                $io->writeln("Existe déjà : <comment>{$username}</comment>");
                continue;
            }

            $user = (new User())
                ->setUsername($username)
                ->setEmail("{$username}@example.test")
                ->setFullName($fullName)
                ->setRole($role)
                ->setIsActive(true)
                ->setIsVerified(true);

            $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
            $this->em->persist($user);
            $io->writeln("Créé : <info>{$username}</info> / {$plain}");
        }

        $this->em->flush();
        $io->success('Terminé.');

        return Command::SUCCESS;
    }
}
