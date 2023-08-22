<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Traits\AmountTrait;
use App\Entity\Traits\FkPaymentTrait;
use App\Entity\Traits\DateTrait;


/**
 * PaymentSplit
 *
 * @ORM\Table(name="payment_split")
 * @ORM\Entity(repositoryClass="App\Repository\PaymentSplitRepository")
 */
class PaymentSplit
{  
    use AmountTrait;
    use FkPaymentTrait;
    use DateTrait;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id_payment_split", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPaymentSplit;
    
    /**
     * Get idPaymentSplit
     *
     * @return integer 
     */
    public function getIdPaymentSplit()
    {
        return $this->idPaymentSplit;
    }
}