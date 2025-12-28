<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'distribution')]
#[ORM\Entity(repositoryClass: \App\Repository\DistributionRepository::class)]
class Distribution
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")]
    #[ORM\Column(name: 'id_distribution', type: 'integer')]
    private $idDistribution;

    #[ORM\Column(name: 'date', type: 'date', nullable: false)]
    private $date;

    #[ORM\Column(name: 'info_livraison', type: 'text', nullable: true)]
    private $infoLivraison;

    #[ORM\Column(name: 'info_distribution', type: 'text', nullable: true)]
    private $infoDistribution;

    #[ORM\Column(name: 'info_divers', type: 'text', nullable: true)]
    private $infoDivers;


    /**
     * Get idDistribution
     *
     * @return integer 
     */
    public function getIdDistribution()
    {
        return $this->idDistribution;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Distribution
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }
    
    public function getInfoLivraison() {
        return $this->infoLivraison;
    }
    
    public function setInfoLivraison($infoLivraison) {
        $this-> infoLivraison = $infoLivraison;
        return $this;
    }
    
    public function getInfoDistribution() {
        return $this->infoDistribution;
    }
    
    public function setInfoDistribution($infoDistribution) {
        $this-> infoDistribution = $infoDistribution;
        return $this;
    }
    
    public function getInfoDivers() {
        return $this->infoDivers;
    }
    
    public function setInfoDivers($infoDivers) {
        $this-> infoDivers = $infoDivers;
        return $this;
    }
}
