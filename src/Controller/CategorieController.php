<?php

namespace App\Controller;

use App\Entity\Categorie;


use App\Entity\Produit;
use App\Form\CategorieForm;

use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/categorie')]
final class CategorieController extends AbstractController
{
    #[Route('/admin/liste', name: 'categorie_liste')]
    #[IsGranted('ROLE_ADMIN')]
    public function liste(Request $request, EntityManagerInterface $entityManager, PaginatorInterface $paginator): Response
    {
        $repository = $entityManager->getRepository(Categorie::class);
        $queryBuilder = $repository->createQueryBuilder('c');
        $categories = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('categorie/liste.html.twig', [
            'categories' => $categories,
        ]);
    }



    #[Route('/new/{categorie?}', name: 'categorie_new')]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, ?Categorie $categorie = null): Response
    {

        $isNew = false;
        if (! $categorie) {
            $categorie = new Categorie();
            $isNew = true;
        }

        $form = $this->createForm(CategorieForm::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($categorie);
            $entityManager->flush();
            $message = $isNew ? 'Categorie ajouté avec succès' : 'Categorie modifié avec succès';
            $this->addFlash('success', $message);
            return $this->redirectToRoute('categorie_liste');
        }

        return $this->render('categorie/new.html.twig', [
            'categorie' => $categorie,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/supprimer/{categorie}', name: 'categorie_supprimer')]
    public function supprimer(Request $request, EntityManagerInterface $entityManager, ?Categorie $categorie = null): Response
    {
        if ($categorie) {
            $entityManager->remove($categorie);
            $entityManager->flush();
            $this->addFlash("success", "Categorie supprimé avec succès");
        } else {
            $this->addFlash("error", "Categorie introuvable");
        }
        return $this->redirectToRoute('categorie_liste');
    }
    #[Route('/produits/{categorie}', name: 'categorie_produits')]
    public function listeProduitsParCategorie(Categorie $categorie, EntityManagerInterface $entityManager, PaginatorInterface $paginator, Request $request): Response
    {
        $produitRepo = $entityManager->getRepository(Produit::class);
        $categorieRepo = $entityManager->getRepository(Categorie::class);

        $queryBuilder = $produitRepo->createQueryBuilder('p')
            ->andWhere('p.categorie = :cat')
            ->setParameter('cat', $categorie);

        $produits = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            6
        );

        $categories = $categorieRepo->findAll();

        return $this->render('produit/liste.html.twig', [
            'categorie' => $categorie,
            'produits' => $produits,
            'categories' => $categories,
        ]);
    }
}
