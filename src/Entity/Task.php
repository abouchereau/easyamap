<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\LabelTrait;
use App\Entity\Traits\IsActiveDefaultTrueTrait;
/**
 * Setting
 *
 * @ORM\Table(name="task")
 * @ORM\Entity(repositoryClass="App\Repository\TaskRepository")
 */
class Task
{
    use LabelTrait;
    use IsActiveDefaultTrueTrait;
   /**
     * @var integer
     *
     * @ORM\Column(name="id_task", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idTask;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="min", type="integer", nullable=true)
     */
    private $min;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="max", type="integer", nullable=true)
     */
    private $max;
    
    function getIdTask() {
        return $this->idTask;
    }

    function setIdTask($idTask) {
        $this->idTask = $idTask;
        return $this;
    }
    
    function getMin() {
        return $this->min;
    }

    function getMax() {
        return $this->max;
    }

    function setMin($min) {
        $this->min = $min;
        return $this;
    }

    function setMax($max) {
        $this->max = $max;
        return $this;
    }


    
}
