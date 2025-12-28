<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkUserTrait;
use App\Entity\Traits\FkFarmTrait;

#[ORM\Table(name: 'referent', indexes: [new ORM\Index(name: 'fk_user', columns: ['fk_user']), new ORM\Index(name: 'fk_farm', columns: ['fk_farm'])])]
#[ORM\Entity(repositoryClass: \App\Repository\ReferentRepository::class)]
class Referent
{  
    use FkUserTrait;
    use FkFarmTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_referent', type: 'integer')]
    private $idReferent;



    /**
     * Get idReferent
     *
     * @return integer 
     */
    public function getIdReferent()
    {
        return $this->idReferent;
    }  


}
