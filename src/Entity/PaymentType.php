<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\LabelTrait;

#[ORM\Table(name: 'payment_type', uniqueConstraints: [new ORM\UniqueConstraint(name: 'label', columns: ['label'])])]
#[ORM\Entity]
class PaymentType
{
    const CHECK  = 1;
    const CASH   = 2;
    const PAYPAL = 3;
    
    
    use LabelTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_payment_type', type: 'integer')]
    private $idPaymentType;
    
    /**
     * Get idPaymentType
     *
     * @return integer 
     */
    public function getIdPaymentType()
    {
        return $this->idPaymentType;
    }
    
    static public function getLabel($type) {
        switch ($type) {
            case self::CHECK: return "Chèque"; break;
            case self::CASH: return "Espèces"; break;
            case self::PAYPAL: return "PayPal"; break;
        }
        return false;
    }
    
    public function __toString() {
        return $this->label;
    }

}