<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkTaskTrait
{
    /**
     * @var \Task
     *
     * @ORM\ManyToOne(targetEntity="Task")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="fk_task", referencedColumnName="id_task")
     * })
     */
    private $fkTask;
    

    public function setFkTask(\App\Entity\Task $fkTask = null)
    {
        $this->fkTask = $fkTask;

        return $this;
    }

    /**
     * Get fkTask
     *
     * @return \App\Entity\Task
     */
    public function getFkTask()
    {
        return $this->fkTask;
    }
}