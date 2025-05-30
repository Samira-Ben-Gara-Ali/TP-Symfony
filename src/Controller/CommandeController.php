<?php

namespace App\Controller;

use App\Entity\ArticleCommande;
use App\Entity\Commande;
use App\Repository\UserRepository;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/commande')]
final class CommandeController extends AbstractController
{
    private $panierService;
    private $entityManager;
    private $utilisateurRepository;
    private $security;

    public function __construct(PanierService $panierService,
                                EntityManagerInterface $entityManager,
                                UserRepository $utilisateurRepository,
                                Security $security

    ){
        $this->panierService = $panierService;
        $this->entityManager = $entityManager;
        $this->utilisateurRepository = $utilisateurRepository;
        $this->security = $security;

    }
    #[Route('/liste', name: 'commande_liste')]
    public function liste(): Response
    {

        $utilisateur = $this->security->getUser();



        $commandes = $this->entityManager->getRepository(Commande::class)->findBy(['utilisateur' => $utilisateur], ['dateCommande' => 'DESC']);

        return $this->render('commande/liste.html.twig', [
            'commandes' => $commandes,
        ]);
    }
    #[Route('/finaliser', name: 'commande_finaliser') ]
    public function finaliser(): Response
    {
        $panier = $this->panierService->getPanier();
        $total = $this->panierService->getTotal();
        if($total<=0){
            $this->addFlash('danger', 'Votre Panier est Vide ');
            return $this->redirectToRoute('panier_index');
        }
        $commande = new Commande();

        $utilisateur = $this->security->getUser();

        if($utilisateur){
            $commande->setUtilisateur($utilisateur);
        }
        $commande->setTotal($total);

        foreach ($panier->getArticles() as $articlePanier) {
            $produit = $articlePanier->getProduit();
            $quantiteDemandee = $articlePanier->getQuantite();
            $quantiteDisponible = $produit->getQuantite();

            if ($quantiteDemandee > $quantiteDisponible) {
                $this->addFlash('error', sprintf(
                    'Quantité insuffisante pour le produit "%s". Quantité demandée: %d, Disponible: %d',
                    $produit->getNom(),
                    $quantiteDemandee,
                    $quantiteDisponible
                ));
                return $this->redirectToRoute('panier_index');
            }

            $articleCommande = new ArticleCommande();
            $articleCommande->setCommande($commande);
            $articleCommande->setProduit($articlePanier->getProduit());
            $articleCommande->setQuantite($articlePanier->getQuantite());
            $articleCommande->setPrixUnitaire($articlePanier->getProduit()->getPrix());
            $commande->addArticle($articleCommande);
            $this->entityManager->persist($articleCommande);

            $produit->setQuantite($quantiteDisponible - $quantiteDemandee);
            $this->entityManager->persist($produit);
        }
        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        $this->panierService->vider();
        $this->addFlash('success', 'Votre commande a été finalisé avec succes! Numéro de commande : #'
        .$commande->getId());
        return $this->render('commande/finaliser.html.twig', [
            'commande' => $commande

        ]);

    }
    #[Route('/details/{id}', name: 'commande_details')]
    public function details(Commande $commande): Response
    {
       
        $utilisateur = $this->security->getUser();

        if (!$utilisateur || $commande->getUtilisateur() !== $utilisateur) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à voir cette commande.');
            return $this->redirectToRoute('commande_liste');
        }

        return $this->render('commande/details.html.twig', [
            'commande' => $commande,
        ]);
    }
















}
