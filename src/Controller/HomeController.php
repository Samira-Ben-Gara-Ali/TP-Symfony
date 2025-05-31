<?php

namespace App\Controller;

use App\Repository\ProduitRepository;
use App\Repository\CategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'root')]
    public function root(): Response
    {
        return $this->redirectToRoute('home');
    }

    #[Route('/home', name: 'home')]
    public function index(ProduitRepository $produitRepository, CategorieRepository $categorieRepository): Response
    {
        $featuredProduct = $produitRepository->findOneBy([], ['dateAjout' => 'DESC']);
        $newArrivals = $produitRepository->findBy([], ['dateAjout' => 'DESC'], 8);
        $meilleuresVentes = $newArrivals;
        $categories = $categorieRepository->findAll();

        return $this->render('home/index.html.twig', [
            'featuredProduct' => $featuredProduct,
            'newArrivals' => $newArrivals,
            'meilleuresVentes' => $meilleuresVentes,
            'categories' => $categories,
        ]);
    }
}
