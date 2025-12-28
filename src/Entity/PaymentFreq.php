<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\LabelTrait;

#[ORM\Table(name: 'payment_freq', uniqueConstraints: [new ORM\UniqueConstraint(name: 'label', columns: ['label'])])]
#[ORM\Entity]
class PaymentFreq
{
    const EACH_DISTRIBUTION = 1;
    const EACH_1_MONTH      = 2;
    const EACH_2_MONTH      = 3;
    const EACH_3_MONTH      = 4;
    const EACH_4_MONTH      = 5;
    const EACH_6_MONTH      = 6;
    const EACH_YEAR         = 7;
    
    
    use LabelTrait;
    
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_payment_freq', type: 'integer')]
    private $idPaymentFreq;
    
    /**
     * Get idPaymentFreq
     *
     * @return integer 
     */
    public function getIdPaymentFreq()
    {
        return $this->idPaymentFreq;
    }
    
    static public function getNbMonth($freq) {
        switch ($freq) {
            case self::EACH_1_MONTH: return 1; break;
            case self::EACH_2_MONTH: return 2; break;
            case self::EACH_3_MONTH: return 3; break;
            case self::EACH_4_MONTH: return 4; break;
            case self::EACH_6_MONTH: return 6; break;
            case self::EACH_YEAR: return 12; break;            
        }
        return false;
    }
    
    public function __toString() {
        return $this->label;
    }

}