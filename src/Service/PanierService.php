<?php

namespace App\Service;


use App\Entity\ArticleDuPanier;
use App\Entity\Panier;
use App\Repository\ArticleDuPanierRepository;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class PanierService
{
    private $panierRepository;
    private $articleDuPanierRepository;
    private $produitRepository;
    private $utilisateurRepository;
    private $entityManager;
    private $security;

    public function __construct(
        PanierRepository          $panierRepository,
        ArticleDuPanierRepository $articleDuPanierRepository,
        ProduitRepository         $produitRepository,
        EntityManagerInterface    $entityManager,
        Security                  $security,
        utilisateurRepository      $utilisateurRepository
    )
    {
        $this->panierRepository = $panierRepository;
        $this->articleDuPanierRepository = $articleDuPanierRepository;
        $this->produitRepository = $produitRepository;
        $this->entityManager = $entityManager;
        $this->security = $security;
        $this->utilisateurRepository = $utilisateurRepository;
    }

    public function getPanier(): Panier
    {

        $utilisateur = $this->security->getUser();



        $panier = $utilisateur ? $this->panierRepository->findOneBy(['utilisateur' => $utilisateur]) : null;

        if (!$panier) {
            $panier = new Panier();
            if ($utilisateur) {
                $panier->setUtilisateur($utilisateur);
            }
            $this->entityManager->persist($panier);
            $this->entityManager->flush();
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
            ['produit' => $idProduit,
                'panier' => $panier]
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
        foreach ($panier->getArticles() as $article) {
            $total += $article->getQuantite() * $article->getProduit()->getPrix();
        }
        return $total;
    }
    public function vider(){
        $panier = $this->getPanier();
        foreach ($panier->getArticles() as $article) {
            $this->entityManager->remove($article);
        }
        $this->entityManager->flush();
}


}