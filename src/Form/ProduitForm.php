<?php

namespace App\Form;

use App\Entity\Categorie;
use App\Entity\Produit;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
class ProduitForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('description')
            ->add('prix', TextType::class, [
                'label' => 'Prix',
                'required' => true,
                'attr' => ['placeholder' => '0.00']
            ])
            ->add('auteur')
            ->add('quantite')
            ->add('imageUrl')
            ->add('etat')

            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Sélectionner une catégorie --',
                'required' => true
            ])
            ->add('submit', SubmitType::class, [
                'label' => $options['data']->getId() ? 'Mettre à jour' : 'Ajouter',
                'attr' => [
                    'class' => 'btn btn-primary rounded'
                ]
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
