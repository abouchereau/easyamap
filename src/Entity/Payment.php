<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Traits\FkUserTrait;
use App\Entity\Traits\FkFarmTrait;
use App\Entity\Traits\FkContractTrait;
use App\Entity\Traits\DescriptionTrait;
use App\Entity\Traits\AmountTrait;


/**
 * Payment
 *
 * @ORM\Table(name="payment")
 * @ORM\Entity(repositoryClass="App\Repository\PaymentRepository")
 */
class Payment
{  
  
    use FkUserTrait;
    use FkFarmTrait;
    use FkContractTrait;
    use DescriptionTrait;    
    use AmountTrait;    
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id_payment", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPayment;
    
    /**
     * @var float
     *
     * @ORM\Column(name="received", type="float", precision=10, scale=0, nullable=false)
     */
    private $received;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="received_at", type="date", nullable=true)
     */
    private $receivedAt;
    
    /**
     * Get idPayment
     *
     * @return integer 
     */
    public function getIdPayment()
    {
        return $this->idPayment;
    }
    
    /**
     * Set received
     *
     * @param float $received
     * @return Payment
     */
    public function setReceived($received)
    {
        $this->received = $received;

        return $this;
    }
    
    public function setReceivedEqualAmount()
    {
        $this->received = $this->getAmount();

        return $this;
    }

    /**
     * Get receivedAt
     *
     * @return float 
     */
    public function getReceived()
    {
        return $this->received;
    }
    
        /**
     * Set receivedAt
     *
     * @param \DateTime $receivedAt
     * @return Payment
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * Get receivedAt
     *
     * @return \DateTime 
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }
}