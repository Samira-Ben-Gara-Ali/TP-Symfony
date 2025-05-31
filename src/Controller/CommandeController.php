<?php

namespace App\Controller;

use App\Entity\ArticleCommande;
use App\Entity\Commande;
use App\Repository\UserRepository;
use App\Service\PanierService;
use App\Service\StripeService;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande')]
#[IsGranted('ROLE_USER')]
final class CommandeController extends AbstractController
{
    private $panierService;
    private $entityManager;
    private $userRepository;
    private $security;
    private $stripeService;
    private $mailerService;

    public function __construct(
        PanierService $panierService,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        Security $security,
        StripeService $stripeService,
        MailerService $mailerService
    ) {
        $this->panierService = $panierService;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
        $this->security = $security;
        $this->stripeService = $stripeService;
        $this->mailerService = $mailerService;
    }

    #[Route('/liste', name: 'commande_liste')]
    public function liste(): Response
    {
        $utilisateur = $this->security->getUser();
        $commandes = $this->entityManager->getRepository(Commande::class)->findBy(
            ['utilisateur' => $utilisateur],
            ['dateCommande' => 'DESC']
        );

        return $this->render('commande/liste.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/checkout', name: 'commande_checkout')]
    public function checkout(): Response
    {
        $panier = $this->panierService->getPanier();
        $total = $this->panierService->getTotal();

        $user = $this->security->getUser();


        if ($total <= 0) {
            $this->addFlash('danger', 'Votre panier est vide.');
            return $this->redirectToRoute('panier_index');
        }

        // Vérifier la disponibilité des produits
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
        }

        return $this->render('commande/checkout.html.twig', [
            'articles' => $panier->getArticles(),
            'total' => $total,
            'stripe_public_key' => $this->stripeService->getPublicKey()
        ]);
    }

    #[Route('/create-payment-intent', name: 'commande_create_payment_intent', methods: ['POST'])]
    public function createPaymentIntent(Request $request, MailerService $mailerService): JsonResponse
    {
        $panier = $this->panierService->getPanier();
        $total = $this->panierService->getTotal();

        if ($total <= 0) {
            return new JsonResponse(['error' => 'Panier vide'], 400);
        }

        // Créer la commande temporaire
        $commande = new Commande();
        $utilisateur = $this->security->getUser();

        if ($utilisateur) {
            $commande->setUtilisateur($utilisateur);
        }
        $commande->setTotal($total);
        $commande->setStatut('en_attente');
        $commande->setStatutPaiement('en_attente');

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

        // Créer le Payment Intent Stripe
        $result = $this->stripeService->createPaymentIntent($commande);

        if (!$result['success']) {
            return new JsonResponse(['error' => $result['error']], 500);
        }

        // Sauvegarder l'ID du Payment Intent
        $commande->setStripePaymentIntentId($result['payment_intent_id']);
        $this->entityManager->flush();

        return new JsonResponse([
            'client_secret' => $result['client_secret'],
            'commande_id' => $commande->getId()
        ]);
    }

    #[Route('/confirm-payment/{id}', name: 'commande_confirm_payment', methods: ['POST'])]
    public function confirmPayment(Commande $commande, Request $request): JsonResponse
    {
        $utilisateur = $this->security->getUser();

        if (!$utilisateur || $commande->getUtilisateur() !== $utilisateur) {
            return new JsonResponse(['error' => 'Non autorisé'], 403);
        }

        $paymentIntentId = $commande->getStripePaymentIntentId();
        if (!$paymentIntentId) {
            return new JsonResponse(['error' => 'Payment Intent introuvable'], 400);
        }

        // Vérifier le statut du paiement
        $statutPaiement = $this->stripeService->confirmPaymentStatus($paymentIntentId);
        $commande->setStatutPaiement($statutPaiement);

        if ($statutPaiement === 'paye') {
            $commande->setStatut('confirmee');

            // Déduire les quantités des produits
            foreach ($commande->getArticles() as $articleCommande) {
                $produit = $articleCommande->getProduit();
                $nouvelleQuantite = $produit->getQuantite() - $articleCommande->getQuantite();
                $produit->setQuantite($nouvelleQuantite);
                $this->entityManager->persist($produit);
            }

            // Vider le panier
            $this->panierService->vider();

            $this->entityManager->flush();

            // Send facture email here, after payment is confirmed
            $this->mailerService->sendFacture($commande);

            return new JsonResponse([
                'success' => true,
                'redirect_url' => $this->generateUrl('commande_success', ['id' => $commande->getId()])
            ]);
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => false,
            'error' => 'Le paiement n\'a pas pu être confirmé'
        ]);
    }

    #[Route('/success/{id}', name: 'commande_success')]
    public function success(Commande $commande): Response
    {
        $utilisateur = $this->security->getUser();

        if (!$utilisateur || $commande->getUtilisateur() !== $utilisateur) {
            $this->addFlash('error', 'Vous n\'êtes pas autorisé à voir cette commande.');
            return $this->redirectToRoute('commande_liste');
        }

        if ($commande->getStatutPaiement() !== 'paye') {
            $this->addFlash('warning', 'Cette commande n\'est pas encore payée.');
            return $this->redirectToRoute('commande_liste');
        }

        return $this->render('commande/success.html.twig', [
            'commande' => $commande
        ]);
    }

    // Maintenir l'ancienne méthode pour compatibilité (sans paiement)
    #[Route('/finaliser', name: 'commande_finaliser')]
    public function finaliser(MailerService $mailerService): Response
    {
        $panier = $this->panierService->getPanier();
        $total = $this->panierService->getTotal();
        if ($total <= 0) {
            $this->addFlash('danger', 'Votre Panier est Vide ');
            return $this->redirectToRoute('panier_index');
        }
        $commande = new Commande();

        $utilisateur = $this->security->getUser();

        if ($utilisateur) {
            $commande->setUtilisateur($utilisateur);
        }
        $commande->setTotal($total);
        $commande->setStatut('confirmee');
        $commande->setStatutPaiement('paye'); // Pour les commandes sans Stripe

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

        // Send facture email
        $mailerService->sendFacture($commande);

        $this->panierService->vider();
        $this->addFlash('success', 'Votre commande a été finalisé avec succes! Numéro de commande : #'
            . $commande->getId());
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
