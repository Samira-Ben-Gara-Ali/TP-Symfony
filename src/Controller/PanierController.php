<?php

namespace App\Controller;

use App\Service\PanierService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/panier')]
#[IsGranted('ROLE_USER')]
final class PanierController extends AbstractController
{
    private $panierService;
    public function __construct(PanierService $panierService){
        $this->panierService = $panierService;
    }
    #[Route('/', name: 'panier_index')]
    public function index(): Response
    {
        $panier = $this->panierService->getPanier();
        $total = $this->panierService->getTotal();
        return $this->render('panier/index.html.twig', [
            'articles' => $panier->getArticles(),
            'total' => $total,
        ]);
    }
    #[Route('/ajouter/{id}', name: 'panier_ajouter')]
    public function ajouter( Request $request,int $id): Response
    {
        try {
            $quantite = (int) $request->request->get('quantite',1);



            $this->panierService->ajouter($id, $quantite);
            $this->addFlash('success','Produit ajouté au panier avec succes');
        }catch (\Exception $e){
            $this->addFlash('error',$e->getMessage());
        }
        return $this->redirectToRoute('produit_liste');
    }

    #[Route('/supprimer/{id}', name: 'panier_supprimer')]
    public function supprimer(Request $request,int $id): Response
    {
        try {
            $this->panierService->supprimer($id );
            $this->addFlash('success','Produit supprimé du panier avec succes');
        }catch (\Exception $e){
            $this->addFlash('error',$e->getMessage());
        }
        return $this->redirectToRoute('panier_index');
    }
    #[Route('/mettre-a-jour/{id}', name: 'panier_mettre_a_jour')]
    public function mettreAJour(Request $request,int $id): Response
    {
        try {
            $quantite = (int) $request->request->get('quantite');
            $this->panierService->mettreAJour($id, $quantite);
            $this->addFlash('success','Quantité mise à avec succes');
        }catch (\Exception $e){
            $this->addFlash('error',$e->getMessage());
        }
        return $this->redirectToRoute('panier_index');
    }



}
