<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Traits\LabelTrait;
use App\Entity\Traits\IsActiveDefaultTrueTrait;
use App\Entity\Traits\FkFarmTrait;
use App\Entity\Traits\TraceTimeTrait;
use App\Entity\Traits\SequenceTrait;
use App\Entity\Traits\DescriptionTrait;

#[ORM\Table(name: 'product', uniqueConstraints: [new ORM\UniqueConstraint(name: 'label', columns: ['label', 'unit', 'fk_farm'])], indexes: [new ORM\Index(name: 'fk_farm', columns: ['fk_farm'])])]
#[ORM\Entity(repositoryClass: \App\Repository\ProductRepository::class)]
#[UniqueEntity(fields: ['label', 'unit', 'fkFarm'], message: 'Un produit similaire existe déjà.')]
class Product
{      
    use LabelTrait;
    use IsActiveDefaultTrueTrait;
    use FkFarmTrait;
    use TraceTimeTrait;
    use SequenceTrait;
    use DescriptionTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_product', type: 'integer')]
    private $idProduct;


    #[ORM\Column(name: 'unit', type: 'string', length: 255, nullable: true)]
    private $unit;

    #[ORM\Column(name: 'base_price', type: 'float', precision: 10, scale: 0, nullable: true)]
    private $basePrice;

    #[ORM\Column(name: 'ratio', type: 'string', length: 255, nullable: true)]
    private $ratio;
    
    #[ORM\Column(name: 'is_subscription', type: 'boolean', nullable: false)]
    private $isSubscription = false;
    
    #[ORM\Column(name: 'is_certified', type: 'boolean', nullable: false)]
    private $isCertified = true;





    /** Get idProduct
     *
     * @return integer 
     */
    public function getIdProduct()
    {
        return $this->idProduct;
    }

    /**
     * Set unit
     *
     * @param string $unit
     * @return Product
     */
    public function setUnit($unit)
    {
        $this->unit = $unit;

        return $this;
    }

    /**
     * Get unit
     *
     * @return string 
     */
    public function getUnit()
    {
        return $this->unit;
    }

    /**
     * Set basePrice
     *
     * @param float $basePrice
     * @return Product
     */
    public function setBasePrice($basePrice)
    {
        $this->basePrice = $basePrice;

        return $this;
    }

    /**
     * Get basePrice
     *
     * @return float 
     */
    public function getBasePrice()
    {
        return $this->basePrice;
    }

    
    public function getLabelForCheckbox()
    {
      return '<span class="farm-checkbox">'.$this->getFkFarm().'</span>'.$this->getLabel().' '.$this->getUnit();
    }
    
    public function isActive()
    {
      return $this->isActive && $this->getFkFarm()->getIsActive();
    }
    
    public function getRatio() {
        return $this->ratio!=null;        
    }
    
    /**
     * todo : modifier si autres cas que kg
     * @param type $ratio
     * @return \App\Entity\Product
     */
    public function setRatio($ratio) {
        if (!empty($ratio) || $ratio != 0 || $ratio != '0' || $ratio!=false)
            $this->ratio = 'kg';
        else
            $this->ratio = null;
        return $this;
    }
    
     function getIsSubscription() {
        return $this->isSubscription;
     }

     function setIsSubscription($isSubscription) {
       $this->isSubscription = $isSubscription;
       return $this;
     }
     
     function getIsCertified() {
         return $this->isCertified;
     }

     function setIsCertified($isCertified) {
         $this->isCertified = $isCertified;         
         return $this;
     }


}
