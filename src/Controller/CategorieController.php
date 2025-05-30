<?php

namespace App\Controller;

use App\Entity\Categorie;


use App\Entity\Produit;
use App\Form\CategorieForm;

use App\Repository\CategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/categorie')]
final class CategorieController extends AbstractController
{
    #[Route('/admin/liste', name: 'categorie_liste')]
    public function liste(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $repository = $doctrine->getRepository(Categorie::class);
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
    public function new(Request $request, ManagerRegistry $doctrine, Categorie $categorie =null): Response
    {
        $isNew=false;
        if (! $categorie) {
            $categorie = new Categorie();
            $isNew=true;
        }

        $form = $this->createForm(CategorieForm::class, $categorie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($categorie);
            $entityManager->flush();
            $message = $isNew ? 'Categorie ajouté avec succès' : 'Categorie modifié avec succès';$this->addFlash('success', $message);
            return $this->redirectToRoute('categorie_liste');
        }

        return $this->render('categorie/new.html.twig', [
            'categorie' => $categorie,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/supprimer/{categorie}', name: 'categorie_supprimer')]
    public function supprimer(Request $request, ManagerRegistry $doctrine, Categorie $categorie=null): Response
    {
        if ($categorie){
            $EntityManager = $doctrine->getManager();
            $EntityManager->remove($categorie);
            $EntityManager->flush();
            $this->addFlash("success", "Categorie supprimé avec succès");

        }
        else{
            $this->addFlash("error", "Categorie introuvable");

        }
        return $this->redirectToRoute('categorie_liste');
    }
    #[Route('/produits/{categorie}', name: 'categorie_produits')]
    public function listeProduitsParCategorie(Categorie $categorie, ManagerRegistry $doctrine, PaginatorInterface $paginator, Request $request): Response {
        $produitRepo = $doctrine->getRepository(Produit::class);
        $categorieRepo = $doctrine->getRepository(Categorie::class);

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