<?php

namespace App\Service;


use App\Entity\ArticleDuPanier;
use App\Entity\Panier;
use App\Repository\ArticleDuPanierRepository;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;

class PanierService
{
    private $panierRepository;
    private $articleDuPanierRepository;
    private $produitRepository;
    private $userRepository;
    private $entityManager;
    private $security;

    public function __construct(
        PanierRepository          $panierRepository,
        ArticleDuPanierRepository $articleDuPanierRepository,
        ProduitRepository         $produitRepository,
        EntityManagerInterface    $entityManager,
        Security                  $security,
        UserRepository            $userRepository
    ) {
        $this->panierRepository = $panierRepository;
        $this->articleDuPanierRepository = $articleDuPanierRepository;
        $this->produitRepository = $produitRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->userRepository = $userRepository;
    }

    public function getPanier(): Panier
    {
        $user = $this->security->getUser();
        $panier = null;
        if ($user instanceof User) {
            $panier = $this->panierRepository->findOneBy(['utilisateur' => $user]);
            if (!$panier) {
                $panier = new Panier();
                $panier->setUtilisateur($user);
                $this->entityManager->persist($panier);
                $this->entityManager->flush();
            }
        }
        return $panier;
    }

    public function ajouter(int $idProduit, int $quantite = 1): void
    {
        $produit = $this->produitRepository->find($idProduit);


        if (!$produit) {
            throw new \Exception("Produit introuvable");
        }
        $panier = $this->getPanier();
        $article = $this->articleDuPanierRepository->findOneBy(
            [
                'produit' => $idProduit,
                'panier' => $panier
            ]
        );

        if ($article) {
            $article->setQuantite($article->getQuantite() + $quantite);
        } else {
            $article = new ArticleDuPanier();
            $article->setPanier($panier)
                ->setProduit($produit)
                ->setQuantite($quantite);
            $this->entityManager->persist($article);
        }
        $this->entityManager->flush();
    }

    public function supprimer(int $idProduit): void
    {
        $produit = $this->produitRepository->find($idProduit);
        if (!$produit) {
            throw new \Exception("Produit introuvable");
        }
        $panier = $this->getPanier();
        $article = $this->articleDuPanierRepository->findOneBy(['panier' => $panier, 'produit' => $idProduit]);
        if ($article) {
            $this->entityManager->remove($article);
            $this->entityManager->flush();
        }
    }

    public function mettreAJour(int $idProduit, int $quantite): void
    {
        $produit = $this->produitRepository->find($idProduit);
        if (!$produit) {
            throw new \Exception("Produit introuvable");
        }
        $panier = $this->getPanier();
        $article = $this->articleDuPanierRepository->findOneBy(['produit' => $idProduit, 'panier' => $panier]);
        if ($article) {
            if ($quantite <= 0) {
                $this->entityManager->remove($article);
            } else {
                $article->setQuantite($quantite);
            }
            $this->entityManager->flush();
        }
    }

    public function getTotal(): float
    {
        $panier = $this->getPanier();
        $total = 0;
        if ($panier) {
            $articles = $panier->getArticles();
            if ($articles) {
                foreach ($articles as $article) {
                    if ($article && $article->getProduit()) {
                        $total += $article->getQuantite() * $article->getProduit()->getPrix();
                    }
                }
            }
        }
        return $total;
    }
    public function vider()
    {
        $panier = $this->getPanier();
        foreach ($panier->getArticles() as $article) {
            $this->entityManager->remove($article);
        }
        $this->entityManager->flush();
    }
}
