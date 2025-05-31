<?php

namespace App\Service;

use App\Entity\Commande;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class StripeService
{
    private StripeClient $stripe;
    private string $publicKey;

    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {
        $this->stripe = new StripeClient($this->parameterBag->get('stripe.secret_key'));
        $this->publicKey = $this->parameterBag->get('stripe.public_key');
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Crée un Payment Intent pour une commande
     */
    public function createPaymentIntent(Commande $commande): array
    {
        try {
            // Convertir le montant en centimes (Stripe utilise les centimes)
            $amount = (int) (floatval($commande->getTotal()) * 100);

            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => 'eur', // Ou 'tnd' si Stripe supporte les dinars tunisiens
                'payment_method_types' => ['card'],
                'metadata' => [
                    'commande_id' => $commande->getId(),
                    'utilisateur_id' => $commande->getUtilisateur()->getId(),
                    'site' => 'BookSaw'
                ],
                'description' => sprintf(
                    'Commande #%d - BookSaw - %s',
                    $commande->getId(),
                    $commande->getUtilisateur()->getEmail()
                )
            ]);

            return [
                'success' => true,
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Récupère un Payment Intent
     */
    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Confirme le statut d'un paiement
     */
    public function confirmPaymentStatus(string $paymentIntentId): string
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            return match ($paymentIntent->status) {
                'succeeded' => 'paye',
                'requires_payment_method', 'requires_confirmation' => 'en_attente',
                'canceled' => 'annule',
                default => 'echoue'
            };
        } catch (ApiErrorException $e) {
            return 'echoue';
        }
    }

    /**
     * Crée un remboursement
     */
    public function createRefund(string $paymentIntentId, ?int $amount = null): array
    {
        try {
            $refundData = ['payment_intent' => $paymentIntentId];

            if ($amount !== null) {
                $refundData['amount'] = $amount;
            }

            $refund = $this->stripe->refunds->create($refundData);

            return [
                'success' => true,
                'refund' => $refund
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
