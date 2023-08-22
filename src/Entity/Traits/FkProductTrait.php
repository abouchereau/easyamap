<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkProductTrait
{
    
    /**
     * @var \Product
     *
     * @ORM\ManyToOne(targetEntity="Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="fk_product", referencedColumnName="id_product")
     * })
     */
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