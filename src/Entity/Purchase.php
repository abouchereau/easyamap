<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkUserTrait;
use App\Entity\Traits\FkProductDistributionTrait;
use App\Entity\Traits\FkContractTrait;
/**
 * Purchase
 *
 * @ORM\Table(name="purchase", indexes={@ORM\Index(name="fk_user", columns={"fk_user"})})
 * @ORM\Entity(repositoryClass="App\Repository\PurchaseRepository")
 */
class Purchase
{  
    use FkUserTrait;
    use FkProductDistributionTrait;
    use FkContractTrait;
    /**
     * @var integer
     *
     * @ORM\Column(name="id_purchase", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idPurchase;



    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer", nullable=true)
     */
    private $quantity;




    /**
     * Get idPurchase
     *
     * @return integer 
     */
    public function getIdPurchase()
    {
        return $this->idPurchase;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return Purchase
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

}
