<?php

namespace App\DataFixtures;

use App\Entity\Categorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

class CategorieFixture extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            [
                'nom' => 'Fiction',
                'description' => 'Romans et œuvres de fiction'
            ],
            [
                'nom' => 'Science-Fiction',
                'description' => 'Livres de science-fiction et fantasy'
            ],
            [
                'nom' => 'Biographie',
                'description' => 'Biographies et autobiographies'
            ],
            [
                'nom' => 'Histoire',
                'description' => 'Livres sur l\'histoire et les événements historiques'
            ],
            [
                'nom' => 'Développement Personnel',
                'description' => 'Livres sur le développement personnel et la motivation'
            ],
            [
                'nom' => 'Policier',
                'description' => 'Romans policiers et thrillers'
            ],
            [
                'nom' => 'Jeunesse',
                'description' => 'Livres pour enfants et adolescents'
            ]
        ];

        foreach ($categories as $categorieData) {
            $categorie = new Categorie();
            $categorie->setNom($categorieData['nom']);
            $categorie->setDescription($categorieData['description']);
            $manager->persist($categorie);
            $this->addReference('categorie_' . strtolower(str_replace(' ', '_', $categorieData['nom'])), $categorie);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['categorie'];
    }
}
