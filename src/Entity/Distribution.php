<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Distribution
 *
 * @ORM\Table(name="distribution")
 * @ORM\Entity(repositoryClass="App\Repository\DistributionRepository")
 */
class Distribution
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id_distribution", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idDistribution;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;



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
}
