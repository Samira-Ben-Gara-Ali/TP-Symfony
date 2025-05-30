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
        // Récupérer les derniers produits ajoutés (nouveautés)
        $nouveautes = $produitRepository->findBy([], ['dateAjout' => 'DESC'], 4);

        // Récupérer quelques produits aléatoires comme "meilleures ventes"
        $meilleuresVentes = $produitRepository->findBy([], ['id' => 'ASC'], 4);

        // Récupérer toutes les catégories
        $categories = $categorieRepository->findAll();

        return $this->render('home/index.html.twig', [
            'nouveautes' => $nouveautes,
            'meilleuresVentes' => $meilleuresVentes,
            'categories' => $categories,
        ]);
    }
}
