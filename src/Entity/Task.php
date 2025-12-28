<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\LabelTrait;
use App\Entity\Traits\IsActiveDefaultTrueTrait;

#[ORM\Table(name: 'task')]
#[ORM\Entity(repositoryClass: \App\Repository\TaskRepository::class)]
class Task
{
    use LabelTrait;
    use IsActiveDefaultTrueTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_task', type: 'integer')]
    private ?int $idTask = null;

    #[ORM\Column(name: 'min', type: 'integer', nullable: true)]
    private ?int $min = null;

    #[ORM\Column(name: 'max', type: 'integer', nullable: true)]
    private ?int $max = null;

    public function getIdTask(): ?int
    {
        return $this->idTask;
    }

    public function setIdTask(?int $idTask): static
    {
        $this->idTask = $idTask;

        return $this;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function setMin(?int $min): static
    {
        $this->min = $min;

        return $this;
    }

    public function setMax(?int $max): static
    {
        $this->max = $max;

        return $this;
    }

}
