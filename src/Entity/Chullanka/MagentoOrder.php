<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\MagentoOrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MagentoOrderRepository::class)
 * @ORM\Table(name="magento_order")
 */
class MagentoOrder
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
     * @ORM\Column(type="string", length=20)
     */
    private $code;

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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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
