<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkContractTrait;
use App\Entity\Traits\FkProductTrait;

#[ORM\Table(name: 'contract_product', indexes: [new ORM\Index(name: 'fk_contract', columns: ['fk_contract']), new ORM\Index(name: 'fk_product', columns: ['fk_product'])])]
#[ORM\Entity(repositoryClass: \App\Repository\ContractProductRepository::class)]
class ContractProduct
{
    use FkContractTrait;
    use FkProductTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_contract_product', type: 'integer')]
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
