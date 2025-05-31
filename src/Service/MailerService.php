<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Commande;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Symfony\Component\Mime\Address;
use Symfony\Component\HttpFoundation\Response;

class MailerService
{
    private $mailer;
    private $twig;
    private $from;

    public function __construct(MailerInterface $mailer, Environment $twig)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->from = $_ENV['MAILER_FROM'] ?? 'drissia043@gmail.com';
    }

    public function sendWelcome(User $user): void
    {
        $email = (new Email())
            ->from(new Address($this->from, 'BookSaw'))
            ->to($user->getEmail())
            ->subject('Bienvenue sur BookSaw !')
            ->html($this->twig->render('emails/welcome.html.twig', [
                'user' => $user
            ]));
        $this->mailer->send($email);
    }

    public function sendFacture(Commande $commande): void
    {
        $user = $commande->getUtilisateur();
        if (!$user) return;
        $email = (new Email())
            ->from(new Address($this->from, 'BookSaw'))
            ->to($user->getEmail())
            ->subject('Votre facture BookSaw - Commande #' . $commande->getId())
            ->html($this->twig->render('emails/facture.html.twig', [
                'commande' => $commande,
                'user' => $user
            ]));
        $this->mailer->send($email);
    }
}
