<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixture extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin1 = new User();
        $admin1->setEmail("admin@gmail.com");
        $admin1->setPassword($this->hasher->hashPassword($admin1, 'admin'));
        $admin1->setRoles(["ROLE_ADMIN"]);
        $admin1->setNom("Admin One");

        $admin2 = new User();
        $admin2->setEmail("admin2@gmail.com");
        $admin2->setPassword($this->hasher->hashPassword($admin2, 'admin'));
        $admin2->setRoles(["ROLE_ADMIN"]);
        $admin2->setNom("Admin Two");
        $manager->persist($admin1);
        $manager->persist($admin2);
        for ($i = 1; $i <= 5; $i++) {
            $user = new User();
            $user->setEmail("user$i@gmail.com");
            $user->setPassword($this->hasher->hashPassword($user, "user"));
            $user->setNom("User $i");
            $manager->persist($user);
        }



        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['user'];
    }
}
