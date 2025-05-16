<?php

namespace App\Controller;

use App\Entity\ArticleCommande;
use App\Entity\Commande;
use App\Repository\UtilisateurRepository;
use App\Service\PanierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/commande')]
final class CommandeController extends AbstractController
{
    private $panierService;
    private $entityManager;
    private $utilisateurRepository;
    public function __construct(PanierService $panierService,
                                EntityManagerInterface $entityManager,
                                UtilisateurRepository $utilisateurRepository){
        $this->panierService = $panierService;
        $this->entityManager = $entityManager;
        $this->utilisateurRepository = $utilisateurRepository;
    }
    #[Route('/liste', name: 'commande_liste')]
    public function liste(): Response
    {
        // à modifier par yassine aprés avoir etablir la couche securité pour recuperer
        // l'utilisateur connecté
        $utilisateur = $this->utilisateurRepository->findOneBy(['id'=>1]);



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
        // à modifier par yassine aprés avoir etablir la couche securité pour recuperer
        // l'utilisateur connecté
        $utilisateur = $this->utilisateurRepository->findOneBy(['id'=>1]);

        if($utilisateur){
            $commande->setUtilisateur($utilisateur);
        }
        $commande->setTotal($total);

        foreach ($panier->getArticles() as $articlePanier) {
            $articleCommande = new ArticleCommande();
            $articleCommande->setCommande($commande);
            $articleCommande->setProduit($articlePanier->getProduit());
            $articleCommande->setQuantite($articlePanier->getQuantite());
            $articleCommande->setPrixUnitaire($articlePanier->getProduit()->getPrix());
            $commande->addArticle($articleCommande);
            $this->entityManager->persist($articleCommande);
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
        // à modifier par yassine aprés avoir etablir la couche securité pour recuperer
        // l'utilisateur connecté
        $utilisateur = $this->utilisateurRepository->findOneBy(['id'=>1]);
        if (!$utilisateur || $commande->getUtilisateur() !== $utilisateur) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à voir cette commande.');
            return $this->redirectToRoute('commande_liste');
        }

        return $this->render('commande/details.html.twig', [
            'commande' => $commande,
        ]);
    }
















}
