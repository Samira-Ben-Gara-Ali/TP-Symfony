<?php

namespace App\DataFixtures;

use App\Entity\Produit;
use App\Entity\Categorie;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProduitFixture extends Fixture implements FixtureGroupInterface, DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $produits = [
            [
                'nom' => 'System Design Interview - An Insider\'s Guide ',
                'description' => 'Un guide complet sur la conception de systèmes, couvrant les principes fondamentaux et les meilleures pratiques.',
                'prix' => 29.90,
                'quantite' => 15,
                'auteur' => 'Alex Xu',
                'categorie' => 'categorie_développement_personnel',
                'imageUrl' => 'https://encrypted-tbn1.gstatic.com/images?q=tbn:ANd9GcTqXhlvxwUkn9WqewK0WNmicOOWB_WcKui3yWgr0tlR5S6uXKkY'
            ],
            [
                'nom' => 'The Lincoln Highway',
                'description' => 'Un road trip épique à travers l\'Amérique des années 1950, où deux frères tentent de retrouver leur mère disparue.',
                'prix' => 16.90,
                'quantite' => 30,
                'auteur' => 'Amor Towles',
                'categorie' => 'categorie_fiction',
                'imageUrl' => 'https://firebasestorage.googleapis.com/v0/b/rmab-react.appspot.com/o/covers%2FVLtOHSXsN1tNgfyQqdwD%2Fmedium.jpeg?alt=media'
            ],
            [
                'nom' => 'Unreliable Memoirs',
                'description' => 'Une autobiographie hilarante et touchante de Clive James, explorant son enfance en Australie et ses débuts dans le monde littéraire.',
                'prix' => 18.90,
                'quantite' => 35,
                'auteur' => 'Clive James',
                'categorie' => 'categorie_biographie',
                'imageUrl' => 'https://firebasestorage.googleapis.com/v0/b/rmab-react.appspot.com/o/covers%2F5KBqDwE4oQ0lEK6zuQNf%2Fmedium.jpeg?alt=media'
            ],
            [
                'nom' => 'The Fault in Our Stars',
                'description' => 'Une histoire d\'amour poignante entre deux adolescents atteints de cancer, qui se battent pour vivre pleinement malgré leur maladie.',
                'prix' => 19.90,
                'quantite' => 25,
                'auteur' => 'John Green',
                'categorie' => 'categorie_jeunesse',
                'imageUrl' => 'https://firebasestorage.googleapis.com/v0/b/rmab-react.appspot.com/o/covers%2FdaOkdEvHdjMe6Gxt7O8h%2Fmedium.jpeg?alt=media'
            ],
            [
                'nom' => 'Poor Economics',
                'description' => 'Une analyse révolutionnaire de la pauvreté et des solutions pratiques pour la combattre, basée sur des années de recherche sur le terrain.',
                'prix' => 22.90,
                'quantite' => 20,
                'auteur' => 'Abhijit V. Banerjee',
                'categorie' => 'categorie_développement_personnel',
                'imageUrl' => 'https://firebasestorage.googleapis.com/v0/b/rmab-react.appspot.com/o/covers%2FWo27DP1iidbK7NIPx1bJ%2Fmedium.jpeg?alt=media'
            ],
            [
                'nom' => 'The Windup Girl',
                'description' => 'Un thriller dystopique se déroulant dans un Bangkok post-pétrole, où les biotechnologies et la manipulation génétique dominent le monde.',
                'prix' => 8.90,
                'quantite' => 50,
                'auteur' => 'Paolo Bacigalupi',
                'categorie' => 'categorie_science-fiction',
                'imageUrl' => 'https://firebasestorage.googleapis.com/v0/b/rmab-react.appspot.com/o/covers%2FHmicdRlAEuBppetRH28x%2Fmedium.jpeg?alt=media'
            ],
            [
                'nom' => 'The Tetris Effect',
                'description' => 'L\'histoire fascinante de la création et de l\'évolution du jeu Tetris, de ses origines soviétiques à son succès mondial.',
                'prix' => 21.90,
                'quantite' => 30,
                'auteur' => 'Dan Ackerman',
                'categorie' => 'categorie_histoire',
                'imageUrl' => 'https://m.media-amazon.com/images/I/61jL8GfFrVL._SL1500_.jpg'
            ]
        ];

        foreach ($produits as $produitData) {
            $produit = new Produit();
            $produit->setNom($produitData['nom']);
            $produit->setDescription($produitData['description']);
            $produit->setPrix($produitData['prix']);
            $produit->setQuantite($produitData['quantite']);
            $produit->setAuteur($produitData['auteur']);
            $produit->setImageUrl($produitData['imageUrl']);
            $produit->setDateAjout(new \DateTimeImmutable());
            $produit->setEtat('disponible');

            if (isset($produitData['categorie'])) {
                $produit->setCategorie($this->getReference($produitData['categorie'], Categorie::class));
            }

            $manager->persist($produit);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['produit'];
    }

    public function getDependencies(): array
    {
        return [
            CategorieFixture::class,
        ];
    }
}
