<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Produit;
use App\Form\ProduitForm;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/produit')]
final class ProduitController extends AbstractController
{

    #[Route('/liste', name: 'produit_liste')]
    public function liste(Request $request, PaginatorInterface $paginator, ManagerRegistry $doctrine): Response
    {
        $repository = $doctrine->getRepository(Produit::class);
        $categorieRepository = $doctrine->getRepository(Categorie::class);
        $categories = $categorieRepository->findAll();

        $search = $request->query->get('search');
        $minPrice = $request->query->get('minPrice');
        $maxPrice = $request->query->get('maxPrice');
        $auteur = $request->query->get('auteur');
        $categorieId = $request->query->get('categorie');
        $sort = $request->query->get('sort');

        $queryBuilder = $repository->createQueryBuilder('p');

        if ($search) {
            $queryBuilder->andWhere('p.nom LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($minPrice) {
            $queryBuilder->andWhere('p.prix >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice) {
            $queryBuilder->andWhere('p.prix <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        if ($auteur) {
            $queryBuilder->andWhere('p.auteur LIKE :auteur')
                ->setParameter('auteur', '%' . $auteur . '%');
        }

        if ($categorieId) {
            $queryBuilder->andWhere('p.categorie = :categorie')
                ->setParameter('categorie', $categorieId);
        }

        if ($sort === 'asc') {
            $queryBuilder->orderBy('p.prix', 'ASC');
        } elseif ($sort === 'desc') {
            $queryBuilder->orderBy('p.prix', 'DESC');
        }

        $produits = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            6,
            [
                'sortFieldParameterName' => null,
                'sortDirectionParameterName' => null
            ]
        );

        return $this->render('produit/liste.html.twig', [
            'produits' => $produits,
            'categories' => $categories,
        ]);
    }


#[Route('/new/{produit?}', name: 'produit_new')]
    public function new(Request $request, ManagerRegistry $doctrine, Produit $produit=null): Response
    {
        $isNew=false;
        if (! $produit) {
            $produit = new Produit();
            $produit->setDateAjout(new \DateTimeImmutable());
            $isNew=true;
        }

        $form = $this->createForm(ProduitForm::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $doctrine->getManager();
            $entityManager->persist($produit);
            $entityManager->flush();
            $message = $isNew ? 'Produit ajouté avec succès' : 'Produit modifié avec succès';
            $this->addFlash('success', $message);
            return $this->redirectToRoute('produit_liste');
        }

        return $this->render('produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/details/{produit}', name: 'produit_details')]
    public function show( SessionInterface $session, ManagerRegistry $doctrine,  Produit $produit=null ): Response
    {

        if ($produit){
            $categorieRepository = $doctrine->getRepository(Categorie::class);

            $categories = $categorieRepository->findAll();
            return $this->render('produit/details.html.twig', ['produit' => $produit, 'categories' => $categories]);}
        else{
            $this->addFlash("error", "Produit introuvable");
            return$this->redirectToRoute('produit_liste');
        }
    }

    #[Route('/supprimer/{produit}', name: 'produit_supprimer')]
    public function supprimer(Request $request, ManagerRegistry $doctrine, Produit $produit=null,): Response
    {
        if ($produit){
            $categorie = $produit->getCategorie();
            if ($categorie) {
                $categorie->removeProduit($produit);
            }
            $EntityManager = $doctrine->getManager();
            $EntityManager->remove($produit);
            $EntityManager->flush();
            $this->addFlash("success", "Produit supprimé avec succès");

        }
        else{
            $this->addFlash("error", "Produit introuvable");

        }
        return $this->redirectToRoute('produit_liste');
}
    #[Route('/admin/liste', name: 'produit_admin_liste')]
    public function adminListe(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $repository = $doctrine->getRepository(Produit::class);
        $queryBuilder = $repository->createQueryBuilder('p');

        $produits = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('produit/liste_admin.html.twig', [
            'produits' => $produits
        ]);
    }

}
