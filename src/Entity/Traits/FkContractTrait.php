<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkContractTrait
{
        #[ORM\ManyToOne(targetEntity: \App\Entity\Contract::class)]
        #[ORM\JoinColumn(name: 'fk_contract', referencedColumnName: 'id_contract')]
        private $fkContract;
    

    public function setFkContract(\App\Entity\Contract $fkContract = null)
    {
        $this->fkContract = $fkContract;

        return $this;
    }

    /**
     * Get fkContract
     *
     * @return \App\Entity\Contract 
     */
    public function getFkContract()
    {
        return $this->fkContract;
    }
}