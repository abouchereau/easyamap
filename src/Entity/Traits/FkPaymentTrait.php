<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkPaymentTrait
{
   /**
     * @var \Payment
     *
     * @ORM\ManyToOne(targetEntity="Payment")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="fk_payment", referencedColumnName="id_payment")
     * })
     */
    private $fkPayment;
    

    public function setFkPayment(\App\Entity\Payment $fkPayment = null)
    {
        $this->fkPayment = $fkPayment;

        return $this;
    }

    /**
     * Get fkPayment
     *
     * @return \App\Entity\Payment 
     */
    public function getFkPayment()
    {
        return $this->fkPayment;
    }
}