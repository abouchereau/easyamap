<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkUserTrait;
use App\Entity\Traits\FkFarmTrait;
/**
 * Referent
 *
 * @ORM\Table(name="referent", indexes={@ORM\Index(name="fk_user", columns={"fk_user"}), @ORM\Index(name="fk_farm", columns={"fk_farm"})})
 * @ORM\Entity(repositoryClass="App\Repository\ReferentRepository")
 */
class Referent
{  
    use FkUserTrait;
    use FkFarmTrait;
    /**
     * @var integer
     *
     * @ORM\Column(name="id_referent", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
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
