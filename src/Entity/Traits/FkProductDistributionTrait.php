<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkProductDistributionTrait
{
    #[ORM\ManyToOne(targetEntity: \App\Entity\ProductDistribution::class)]
    #[ORM\JoinColumn(name: 'fk_product_distribution', referencedColumnName: 'id_product_distribution')]
    private $fkProductDistribution;
    

    public function setFkProductDistribution(\App\Entity\ProductDistribution $fkProductDistribution = null)
    {
        $this->fkProductDistribution = $fkProductDistribution;

        return $this;
    }

    /**
     * Get fkProductDistribution
     *
     * @return \App\Entity\ProductDistribution 
     */
    public function getFkProductDistribution()
    {
        return $this->fkProductDistribution;
    }
}