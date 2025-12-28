<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkProductTrait
{
    #[ORM\ManyToOne(targetEntity: \App\Entity\Product::class)]
    #[ORM\JoinColumn(name: 'fk_product', referencedColumnName: 'id_product')]
    private $fkProduct;




    public function setFkProduct(\App\Entity\Product $fkProduct = null)
    {
        $this->fkProduct = $fkProduct;

        return $this;
    }

    /**
     * Get fkProduct
     *
     * @return \App\Entity\Product 
     */
    public function getFkProduct()
    {
        return $this->fkProduct;
    }

}