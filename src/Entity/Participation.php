<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkUserTrait;
use App\Entity\Traits\FkDistributionTrait;
use App\Entity\Traits\FkTaskTrait;

#[ORM\Table(name: 'participation')]
#[ORM\Entity(repositoryClass: \App\Repository\ParticipationRepository::class)]
class Participation
{
    use FkUserTrait;
    use FkDistributionTrait;
    use FkTaskTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_participation', type: 'integer')]
    private $idParticipation;
    
    function getIdParticipation() {
        return $this->idParticipation;
    }

    function setIdParticipation($idParticipation) {
        $this->idParticipation = $idParticipation;
        return $this;
    }


    
}