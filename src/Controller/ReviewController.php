<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\Produit;
use App\Form\ReviewType;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/review')]
class ReviewController extends AbstractController
{
    #[Route('/produit/{id}/add', name: 'app_review_add', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        $review = new Review();
        $review->setUser($this->getUser());
        $review->setProduit($produit);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Votre avis a été publié avec succès !');
            return $this->redirectToRoute('produit_details', ['produit' => $produit->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('review/add.html.twig', [
            'review' => $review,
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/produit/{id}/reviews', name: 'app_review_list', methods: ['GET'])]
    public function list(Produit $produit, ReviewRepository $reviewRepository): Response
    {
        $reviews = $reviewRepository->findBy(['produit' => $produit], ['createdAt' => 'DESC']);

        return $this->render('review/list.html.twig', [
            'reviews' => $reviews,
            'produit' => $produit,
        ]);
    }
}
