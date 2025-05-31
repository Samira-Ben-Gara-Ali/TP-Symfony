<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/profile')]
#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_user_profile', methods: ['GET', 'POST'])]
    public function profile(
        Request $request,
        EntityManagerInterface $entityManager
       
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            // Should not happen due to IsGranted, but as a fallback
            return $this->redirectToRoute('app_login');
        }

        $originalEmail = $user->getEmail();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Check if email was changed
            if ($user->getEmail() !== $originalEmail) {
                // Email has changed, reset verification status and potentially send new verification email
                $user->setIsVerified(false);
                // TODO: Optionally re-send verification email
                // $this->addFlash('info', 'Votre adresse e-mail a été modifiée. Veuillez la vérifier via le lien envoyé.');
                // Consider how to handle email re-verification (e.g., using EmailVerifier service)
            }

            // Handle password change
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('plainPassword')->getData();
            $newPasswordConfirm = $form->get('plainPasswordConfirm')->getData();
            if ($currentPassword || $newPassword || $newPasswordConfirm) {
                if (!$currentPassword || !$newPassword || !$newPasswordConfirm) {
                    $this->addFlash('danger', 'Pour changer votre mot de passe, remplissez tous les champs requis.');
                    return $this->redirectToRoute('app_user_profile');
                }
                // Check current password
                if (!$this->isGranted('IS_AUTHENTICATED_FULLY') || !$this->container->get('security.password_hasher')->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('danger', 'Le mot de passe actuel est incorrect.');
                    return $this->redirectToRoute('app_user_profile');
                }
                // Check new password match
                if ($newPassword !== $newPasswordConfirm) {
                    $this->addFlash('danger', 'Les nouveaux mots de passe ne correspondent pas.');
                    return $this->redirectToRoute('app_user_profile');
                }
                // Hash and set new password
                $user->setPassword(
                    $this->container->get('security.password_hasher')->hashPassword($user, $newPassword)
                );
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');

            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'profileForm' => $form->createView(),
            'user' => $user,
        ]);
    }
}
