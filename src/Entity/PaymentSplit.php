<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Traits\AmountTrait;
use App\Entity\Traits\FkPaymentTrait;
use App\Entity\Traits\DateTrait;


#[ORM\Table(name: 'payment_split')]
#[ORM\Entity(repositoryClass: \App\Repository\PaymentSplitRepository::class)]
class PaymentSplit
{  
    use AmountTrait;
    use FkPaymentTrait;
    use DateTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_payment_split', type: 'integer')]
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