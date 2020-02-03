<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\FkUserTrait;
/**
 * Contract
 *
 * @ORM\Table(name="booth")
 * @ORM\Entity(repositoryClass="App\Repository\BoothRepository")
 */
class Booth
{  
    use FkUserTrait;
    /**
     * @var integer
     *
     * @ORM\Column(name="id_booth", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $idBooth;
    
    /**
     * @var string
     *
     * @ORM\Column(name="route", type="string", length=255, nullable=true)
     */
    private $route;
    
    /**
     * @var string
     *
     * @ORM\Column(name="params", type="string", length=255, nullable=true)
     */
    private $params;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    private $startedAt;
    
    /**
     * Get idBooth
     *
     * @return integer 
     */
    public function getIdBooth()
    {
        return $this->idBooth;
    }
    
   /**
   * Set startedAt
   *
   * @param \DateTime $startedAt
   * @return Booth
   */
  public function setStartedAt($startedAt)
  {
      $this->startedAt = $startedAt;

      return $this;
  }
  
  public function setStartedNow()
  {
      return $this->setStartedAt(new \DateTime());
  }

  /**
   * Get startedAt
   *
   * @return \DateTime 
   */
  public function getStartedAt()
  {
      return $this->startedAt;
    }
    
       /**
     * Set route
     *
     * @param string $route
     * @return Booth
     */
    public function setRoute($route)
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Get route
     *
     * @return string 
     */
    public function getRoute()
    {
        return $this->route;
    }
    
       /**
     * Set params
     *
     * @param string $params
     * @return Booth
     */
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Get params
     *
     * @return string 
     */
    public function getParams()
    {
        return $this->params;
    }
}