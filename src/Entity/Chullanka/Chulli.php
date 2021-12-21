<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\ChulliRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass=ChulliRepository::class)
 * @ORM\Table(name="nan_chk_chulli")
 */
class Chulli implements ResourceInterface
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
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $expertise;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $avatar;

    /**
     * @ORM\OneToMany(targetEntity=Chulltest::class, mappedBy="chulli")
     */
    private $tests;

    public function __construct()
    {
        $this->tests = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->firstname . (!empty($this->lastname) ? ' ' . $this->lastname : '');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getExpertise(): ?string
    {
        return $this->expertise;
    }

    public function setExpertise(?string $expertise): self
    {
        $this->expertise = $expertise;

        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return Collection|Chulltest[]
     */
    public function getTests(): Collection
    {
        return $this->tests;
    }

    public function addTests(Chulltest $tests): self
    {
        if (!$this->tests->contains($tests)) {
            $this->tests[] = $tests;
            $tests->setChulli($this);
        }

        return $this;
    }

    public function removeTests(Chulltest $tests): self
    {
        if ($this->tests->removeElement($tests)) {
            // set the owning side to null (unless already changed)
            if ($tests->getChulli() === $this) {
                $tests->setChulli(null);
            }
        }

        return $this;
    }
}