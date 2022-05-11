<?php

namespace App\Entity\Chullanka;

use App\Repository\Chullanka\TaskRepository;
use Doctrine\ORM\Mapping as ORM;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * @ORM\Entity(repositoryClass=TaskRepository::class)
 * @ORM\Table(name="nan_chk_task")
 */
class Task implements ResourceInterface
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
    private $command;

    /**
     * @ORM\Column(type="boolean")
     */
    private $done;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $executed_at;

    public function __construct()
    {
        $this->done = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommand(): ?string
    {
        return $this->command;
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;

        return $this;
    }

    public function getDone(): ?bool
    {
        return $this->done;
    }

    public function setDone(bool $done): self
    {
        $this->done = $done;

        return $this;
    }

    public function getExecutedAt(): ?\DateTime
    {
        return $this->executed_at;
    }

    public function setExecutedAt(?\DateTime $executed_at): self
    {
        $this->executed_at = $executed_at;

        return $this;
    }
}
