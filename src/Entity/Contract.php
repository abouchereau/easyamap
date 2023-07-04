<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Traits\LabelTrait;
use App\Entity\Traits\IsActiveDefaultFalseTrait;
use App\Entity\Traits\IsVisibleDefaultTrueTrait;
use App\Entity\Traits\FkUserTrait;
use App\Entity\Traits\DescriptionTrait;
/**
 * Contract
 *
 * @ORM\Table(name="contract")
 * @ORM\Entity(repositoryClass="App\Repository\ContractRepository")
 */
class Contract
{  
  
    use LabelTrait;
    use IsActiveDefaultFalseTrait;
    use IsVisibleDefaultTrueTrait;
    use FkUserTrait;//créateur du contrat
    use DescriptionTrait;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id_contract", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idContract;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="period_start", type="date", nullable=true)
     */
    private $periodStart;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="period_end", type="date", nullable=true)
     */
    private $periodEnd;


    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fill_date_end", type="date", nullable=true)
     */
    private $fillDateEnd;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fill_date_start", type="date", nullable=true)
     */
    private $fillDateStart;

    /**
     * @var integer
     *
     * @ORM\Column(name="auto_start_hour", type="integer", nullable=true)
     */
    private $autoStartHour;

    /**
     * @var integer
     *
     * @ORM\Column(name="auto_end_hour", type="integer", nullable=true)
     */
    private $autoEndHour;

    /**
    * @ORM\ManyToMany(targetEntity="App\Entity\Product")
     * @JoinTable(name="contract_product",
     *      joinColumns={@JoinColumn(name="fk_contract", referencedColumnName="id_contract")},
     *      inverseJoinColumns={@JoinColumn(name="fk_product", referencedColumnName="id_product")}
     *      )
     **/

     private $products;
     
     
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="count_purchase_since", type="date", nullable=true)
     */
    private $countPurchaseSince;

    /**
     * @var float
     *
     * @ORM\Column(name="discount", type="float", precision=10, scale=0, nullable=true)
     */
    private $discount;

     public function __construct() {
         $this->products = new ArrayCollection();
     }

    /**
     * Get idContract
     *
     * @return integer 
     */
    public function getIdContract()
    {
        return $this->idContract;
    }
    
    /**
     * Set periodStart
     *
     * @param \DateTime $periodStart
     * @return Contract
     */
    public function setPeriodStart($periodStart)
    {
        if ($periodStart != null) {
            $this->periodStart = $periodStart;
        }

        return $this;
    }

    /**
     * Get periodStart
     *
     * @return \DateTime 
     */
    public function getPeriodStart()
    {
        return $this->periodStart;
    }

    /**
     * Set periodEnd
     *
     * @param \DateTime $periodEnd
     * @return Contract
     */
    public function setPeriodEnd($periodEnd)
    {
        if ($periodEnd != null) {
            $this->periodEnd = $periodEnd;
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getAutoStartHour()
    {
        return $this->autoStartHour;
    }

    /**
     * @param int $autoStartHour
     * @return Contract
     */
    public function setAutoStartHour($autoStartHour)
    {
        $this->autoStartHour = $autoStartHour;
        return $this;
    }

    /**
     * @return int
     */
    public function getAutoEndHour()
    {
        return $this->autoEndHour;
    }

    /**
     * @param int $autoEndHour
     * @return Contract
     */
    public function setAutoEndHour($autoEndHour)
    {
        $this->autoEndHour = $autoEndHour;
        return $this;
    }

    /**
     * Get periodEnd
     *
     * @return \DateTime 
     */
    public function getPeriodEnd()
    {
        return $this->periodEnd;
    }
    
    /**
     * Set fillDateEnd
     *
     * @param \DateTime $fillDateEnd
     * @return Contract
     */
    public function setFillDateEnd($fillDateEnd)
    {
        if ($fillDateEnd != null) {
            $this->fillDateEnd = $fillDateEnd;
        }

        return $this;
    }

    /**
     * Get fillDateEnd
     *
     * @return \DateTime 
     */
    
    public function getFillDateEnd()
    {
        return $this->fillDateEnd;
    }

    /**
     * Set fillDateEnd
     *
     * @param \DateTime $fillDateStart
     * @return Contract
     */
    public function setFillDateStart($fillDateStart)
    {
        if ($fillDateStart != null) {
            $this->fillDateStart = $fillDateStart;
        }

        return $this;
    }

    /**
     * Get fillDateEnd
     *
     * @return \DateTime
     */

    public function getFillDateStart()
    {
        return $this->fillDateStart;
    }
    
    
   /**
     * Set countPurchaseSince
     *
     * @param \DateTime $countPurchaseSince
     * @return Contract
     */
    public function setCountPurchaseSince($countPurchaseSince)
    {
        if ($countPurchaseSince != null) {
            $this->countPurchaseSince = $countPurchaseSince;
        }

        return $this;
    }

    /**
     * Get countPurchaseSince
     *
     * @return \DateTime 
     */
    public function getCountPurchaseSince()
    {
        return $this->countPurchaseSince;
    }
    
    public function isArchive() {
        $now = new \DateTime();
        return $now > $this->fillDateEnd;
    }
    
    public function getProducts()
    {
      return $this->products;
    }
    
    public function setProducts(ArrayCollection $products)
    {
      $this->products = $products;
      return $this;
    }
    
    public function addProduct(Product $product) {
        $this->products->add($product);
        return $this;
    }

    /**
     * @return float
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * @param float $discount
     */
    public function setDiscount(float $discount)
    {
        $this->discount = $discount;
    }


    
    public function __toString()
    {
        if ($this->getPeriodStart()->format('m-Y') == $this->getPeriodEnd()->format('m-Y'))            
            return $this->getLabel().' ('.$this->getPeriodStart()->format('m/y').')';
        else
            return $this->getLabel().' ('.$this->getPeriodStart()->format('m').' - '.$this->getPeriodEnd()->format('m/y').')';
    }
}
