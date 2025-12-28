<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Traits\LabelTrait;
use App\Entity\Traits\IsActiveDefaultTrueTrait;
use App\Entity\Traits\DescriptionTrait;
use App\Entity\Traits\SequenceTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'farm', uniqueConstraints: [new ORM\UniqueConstraint(name: 'label', columns: ['label'])])]
#[ORM\Entity(repositoryClass: \App\Repository\FarmRepository::class)]
#[UniqueEntity('label')]
class Farm
{
    use LabelTrait;
    use IsActiveDefaultTrueTrait;
    use DescriptionTrait;
    use SequenceTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_farm', type: 'integer')]
    private $idFarm;

   
    #[ORM\Column(name: 'product_type', type: 'string', length: 255, nullable: true)]
    private $productType;

    
    #[ORM\Column(name: 'check_payable_to', type: 'string', length: 255, nullable: true)]
    private $checkPayableTo;


    #[ORM\Column(name: 'link', type: 'string', length: 255, nullable: true)]
    private $link;
    
    #[ORM\Column(name: 'equitable', type: 'boolean', nullable: false)]
    private $equitable = false;

    
    
    #[ORM\ManyToMany(targetEntity: \App\Entity\User::class)]
    #[ORM\JoinTable(name: 'referent', joinColumns: [new ORM\JoinColumn(name: 'fk_farm', referencedColumnName: 'id_farm')], inverseJoinColumns: [new ORM\JoinColumn(name: 'fk_user', referencedColumnName: 'id_user')])]
    private $referents;
    

        #[ORM\OneToOne(targetEntity: \App\Entity\User::class)]
        #[ORM\JoinColumn(name: 'fk_user', referencedColumnName: 'id_user')]
        private $fkUser;

    
    /**    
     * @Assert\Count(min = 1, minMessage = "Merci de choisir au moins un type de paiement")  
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\PaymentType::class)]
    #[ORM\JoinTable(name: 'farm_payment_type', joinColumns: [new ORM\JoinColumn(name: 'fk_farm', referencedColumnName: 'id_farm')], inverseJoinColumns: [new ORM\JoinColumn(name: 'fk_payment_type', referencedColumnName: 'id_payment_type')])]
    private $payment_types;
    
    
    /**
     * @Assert\Count(min = 1, minMessage = "Merci de choisir au moins une fréquence de paiement") 
     */
    #[ORM\ManyToMany(targetEntity: \App\Entity\PaymentFreq::class)]
    #[ORM\JoinTable(name: 'farm_payment_freq', joinColumns: [new ORM\JoinColumn(name: 'fk_farm', referencedColumnName: 'id_farm')], inverseJoinColumns: [new ORM\JoinColumn(name: 'fk_payment_freq', referencedColumnName: 'id_payment_freq')])]
    private $payment_freqs;
    
    
      // Comme la propriété $categories doit être un ArrayCollection,
      // On doit la définir dans un constructeur :
      public function __construct()
      {
        $this->referents = new ArrayCollection();
        $this->payment_types = new ArrayCollection();
        $this->payment_freqs = new ArrayCollection();
      }
    
    /**
     * Get idFarm
     *
     * @return integer 
     */
    public function getIdFarm()
    {
        return $this->idFarm;
    }

   
    /**
     * Set productType
     *
     * @param string $productType
     * @return Farm
     */
    public function setProductType($productType)
    {
        $this->productType = $productType;

        return $this;
    }

    /**
     * Get productType
     *
     * @return string 
     */
    public function getProductType()
    {
        return $this->productType;
    }
/*
    public function getCheckEachMonth()
    {
        return $this->checkEachMonth;
    }
    
    public function setCheckEachMonth($bool)
    {
        $this->checkEachMonth = $bool;
        return $this;
    }
    
    public function getCheckEachContract()
    {
        return $this->checkEachContract;
    }
    
    public function setCheckEachContract($bool)
    {
        $this->checkEachContract = $bool;
        return $this;
    }
    
    public function isCheckNotEmpty()
    {
        return $this->checkEachMonth == true ||  $this->checkEachContract == true;
    }*/
    
    public function isCheckPaymentTypeNotEmpty() {
        return ($this->payment_types->count() > 0);
    }
    
    public function isCheckPaymentFreqNotEmpty() {
        return ($this->payment_freqs->count() > 0);
    }
    /**
     * Set checkPayableTo
     *
     * @param string $checkPayableTo
     * @return Farm
     */
    public function setCheckPayableTo($checkPayableTo)
    {
        $this->checkPayableTo = $checkPayableTo;

        return $this;
    }

    /**
     * Get checkPayableTo
     *
     * @return string 
     */
    public function getCheckPayableTo()
    {
        return $this->checkPayableTo;
    }

    /**
     * Set link
     *
     * @param string $link
     * @return Farm
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string 
     */
    public function getLink()
    {
        return $this->link;
    }
    
    public function getEquitable() {
        return $this->equitable;
    }
            
    public function setEquitable($equitable) {
        $this->equitable = $equitable;
        return $this;
    }
    
    
    public function getReferents()
    {
      return $this->referents;
    }
    
    public function getPaymentTypes() {
        return $this->payment_types;
    }
    
    public function getPaymentFreqs() {
        return $this->payment_freqs;
    }
    
    public function setFkUser(\App\Entity\User $fkUser = null)
    {
        $this->fkUser = $fkUser;

        return $this;
    }

    
    public function getFkUser()
    {
        return $this->fkUser;
    }
    
    public function __toString()
    {
      return $this->label;
    }
}
