<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\ApproachCustomerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ApproachCustomerRepository::class)
 * @ORM\Table(name="approach_customer")
 */
class ApproachCustomer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $approach;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $email;

    /**
     * @ORM\Column(type="integer")
     */
    private $sylius;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApproach(): ?int
    {
        return $this->approach;
    }

    public function setApproach(int $approach): self
    {
        $this->approach = $approach;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getSylius(): ?int
    {
        return $this->sylius;
    }

    public function setSylius(int $sylius): self
    {
        $this->sylius = $sylius;

        return $this;
    }
}
