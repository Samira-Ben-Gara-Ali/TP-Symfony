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
    name: 'app:create-users',
    description: 'Creates admin and regular users',
)]
class CreateUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@mail.com');
        $admin->setNom('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $admin,
            'admin'
        );
        $admin->setPassword($hashedPassword);

        // Create regular user
        $user = new User();
        $user->setEmail('user@mail.com');
        $user->setNom('Regular User');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            'password'
        );
        $user->setPassword($hashedPassword);

        // Persist users
        $this->entityManager->persist($admin);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('Users created successfully!');
        $output->writeln('Admin credentials:');
        $output->writeln('Email: admin@mail.com');
        $output->writeln('Password: admin');
        $output->writeln('');
        $output->writeln('Regular user credentials:');
        $output->writeln('Email: user@mail.com');
        $output->writeln('Password: password');

        return Command::SUCCESS;
    }
}
