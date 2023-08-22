<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkProductTrait;
use App\Entity\Traits\FkDistributionTrait;
/**
 * ProductDistribution
 *
 * @ORM\Table(name="product_distribution", indexes={@ORM\Index(name="fk_product", columns={"fk_product"}), @ORM\Index(name="fk_distribution", columns={"fk_distribution"})})
 * @ORM\Entity(repositoryClass="App\Repository\ProductDistributionRepository")
 */
class ProductDistribution
{
    use FkProductTrait;
    use FkDistributionTrait;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id_product_distribution", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idProductDistribution;




    
    /**
     * @var float
     *
     * @ORM\Column(name="price", type="float", precision=10, scale=0, nullable=true)
     */
    private $price;
    
    
    /**
     * @var integer
     *
     * @ORM\Column(name="max_quantity", type="integer", nullable=true)
     */
    private $maxQuantity;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_per_user", type="integer", nullable=true)
     */
    private $maxPerUser;
    
    /**
     * @var \Distribution
     *
     * @ORM\ManyToOne(targetEntity="Distribution")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="fk_distribution_shift", referencedColumnName="id_distribution")
     * })
     */
    private $fkDistributionShift;
    

    /**
     * Get idProductDistribution
     *
     * @return integer 
     */
    public function getIdProductDistribution()
    {
        return $this->idProductDistribution;
    }

    /**
     * Get price 
     * @return float
     */
    
    public function getPrice()
    {
      return $this->price;
    }
    
    /**
     * 
     * @param float $price
     * @return \App\Entity\ProductDistribution
     */
    
    public function setPrice($price)
    {
      $this->price = $price;
      return $this;
    }
    
        /**
     * Set max_quantity
     *
     * @param integer $maxQuantity
     * @return ProductDistribution
     */
    public function setMaxQuantity($maxQuantity)
    {
        $this->maxQuantity = $maxQuantity;

        return $this;
    }

    /**
     * Get maxPerUser
     *
     * @return integer 
     */
    public function getMaxPerUser()
    {
        return $this->maxPerUser;
    }
    
            /**
     * Set maxPerUser
     *
     * @param integer $maxPerUser
     * @return ProductDistribution
     */
    public function setMaxPerUser($maxPerUser)
    {
        $this->maxPerUser = $maxPerUser;

        return $this;
    }

    /**
     * Get quantityLimit
     *
     * @return integer 
     */
    public function getMaxQuantity()
    {
        return $this->maxQuantity;
    }
    
    
    public function setFkDistributionShift(\App\Entity\Distribution $fkDistributionShift = null)
    {
        $this->fkDistributionShift = $fkDistributionShift;

        return $this;
    }

    /**
     * Get fkDistributionShift
     *
     * @return \App\Entity\Distribution 
     */
    public function getFkDistributionShift()
    {
        return $this->fkDistributionShift;
    }

}
