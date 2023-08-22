<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkDistributionTrait
{
    /**
     * @var \Distribution
     *
     * @ORM\ManyToOne(targetEntity="Distribution")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="fk_distribution", referencedColumnName="id_distribution")
     * })
     */
    private $fkDistribution;
    

    public function setFkDistribution(\App\Entity\Distribution $fkDistribution = null)
    {
        $this->fkDistribution = $fkDistribution;

        return $this;
    }

    /**
     * Get fkDistribution
     *
     * @return \App\Entity\Distribution 
     */
    public function getFkDistribution()
    {
        return $this->fkDistribution;
    }
}