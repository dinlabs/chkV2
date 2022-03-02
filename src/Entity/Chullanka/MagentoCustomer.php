<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\MagentoCustomerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MagentoCustomerRepository::class)
 * @ORM\Table(name="magento_customer")
 */
class MagentoCustomer
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
    private $magento;

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

    public function getMagento(): ?int
    {
        return $this->magento;
    }

    public function setMagento(int $magento): self
    {
        $this->magento = $magento;

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
