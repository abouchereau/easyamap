<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\LabelTrait;

/**
 * PaymentType
 *
 * @ORM\Table(name="payment_type", uniqueConstraints={@ORM\UniqueConstraint(name="label", columns={"label"})})
 * @ORM\Entity
 */
class PaymentType
{
    const CHECK  = 1;
    const CASH   = 2;
    const VIREMENT = 3;
    
    
    use LabelTrait;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id_payment_type", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
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
            case self::VIREMENT: return "Virement"; break;
        }
        return false;
    }
    
    public function __toString() {
        return $this->label;
    }

}