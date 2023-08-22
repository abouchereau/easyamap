<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkUserTrait;
use App\Entity\Traits\FkDistributionTrait;
use App\Entity\Traits\FkTaskTrait;

/**
 * Participation
 *
 * @ORM\Table(name="participation")
 * @ORM\Entity(repositoryClass="App\Repository\ParticipationRepository")
 */
class Participation
{
    use FkUserTrait;
    use FkDistributionTrait;
    use FkTaskTrait;
    /**
     * @var integer
     *
     * @ORM\Column(name="id_participation", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idParticipation;
    
    function getIdParticipation() {
        return $this->idParticipation;
    }

    function setIdParticipation($idParticipation) {
        $this->idParticipation = $idParticipation;
        return $this;
    }


    
}