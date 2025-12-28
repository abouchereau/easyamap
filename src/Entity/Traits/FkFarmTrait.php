<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkFarmTrait
{
    #[ORM\ManyToOne(targetEntity: \App\Entity\Farm::class)]
    #[ORM\JoinColumn(name: 'fk_farm', referencedColumnName: 'id_farm')]
    private $fkFarm;


    public function setFkFarm(\App\Entity\Farm $fkFarm = null)
    {
        $this->fkFarm = $fkFarm;

        return $this;
    }

    /**
     * Get fkFarm
     *
     * @return \App\Entity\Farm 
     */
    public function getFkFarm()
    {
        return $this->fkFarm;
    }


}