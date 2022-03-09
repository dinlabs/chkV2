<?php

namespace App\Entity\Chullanka;

use App\Entity\Customer\Customer;
use App\Entity\Product\ProductVariant;
use App\Repository\Chullanka\WishlistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WishlistRepository::class)
 * @ORM\Table(name="nan_chk_wishlist")
 */
class Wishlist
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Customer::class, inversedBy="wishlists")
     * @ORM\JoinColumn(nullable=false)
     */
    private $customer;

    /**
     * @ORM\OneToMany(targetEntity=WishlistProduct::class, mappedBy="wishlist", orphanRemoval=true)
     */
    private $wishlistProducts;

    public function __construct()
    {
        $this->variant = new ArrayCollection();
        $this->wishlistProducts = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): self
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * @return Collection|WishlistProduct[]
     */
    public function getWishlistProducts(): Collection
    {
        return $this->wishlistProducts;
    }

    public function addWishlistProduct(WishlistProduct $wishlistProduct): self
    {
        if (!$this->wishlistProducts->contains($wishlistProduct)) {
            $this->wishlistProducts[] = $wishlistProduct;
            $wishlistProduct->setWishlist($this);
        }

        return $this;
    }

    public function removeWishlistProduct(WishlistProduct $wishlistProduct): self
    {
        if ($this->wishlistProducts->removeElement($wishlistProduct)) {
            // set the owning side to null (unless already changed)
            if ($wishlistProduct->getWishlist() === $this) {
                $wishlistProduct->setWishlist(null);
            }
        }

        return $this;
    }
}
