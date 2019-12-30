<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkContractTrait;
use App\Entity\Traits\FkProductTrait;

/**
 * ContractProduct
 *
 * @ORM\Table(name="contract_product", indexes={@ORM\Index(name="fk_contract", columns={"fk_contract"}), @ORM\Index(name="fk_product", columns={"fk_product"})})
 * @ORM\Entity(repositoryClass="App\Repository\ContractProductRepository")
 */
class ContractProduct
{
  
    use FkContractTrait;
    use FkProductTrait;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="id_contract_product", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idContractProduct;





    /**
     * Get idContractProduct
     *
     * @return integer 
     */
    public function getIdContractProduct()
    {
        return $this->idContractProduct;
    }




}
