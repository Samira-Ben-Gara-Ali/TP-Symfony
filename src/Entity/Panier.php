<?php

namespace App\Entity;

use App\Repository\PanierRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
class Panier
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'panier')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $utilisateur = null;


    #[ORM\OneToMany(targetEntity: ArticleDuPanier::class, mappedBy: 'panier', orphanRemoval: true)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(ArticleDuPanier $article): static
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setPanier($this);
        }

        return $this;
    }

    public function removeArticle(ArticleDuPanier $article): static
    {
        if ($this->articles->removeElement($article)) {
            if ($article->getPanier() === $this) {
                $article->setPanier(null);
            }
        }

        return $this;
    }
}
