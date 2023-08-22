<?php
 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait TraceTimeTrait
{
  /**
   * @var \DateTime
   *
   * @ORM\Column(name="created_at", type="datetime", nullable=true)
   */
  private $createdAt;
  
 /**
   * @var \DateTime
   *
   * @ORM\Column(name="updated_at", type="datetime", nullable=true)
   */
  private $updatedAt;
  
  
    /**
   * Set createdAt
   *
   * @param \DateTime $createdAt
   * @return ?
   */
  public function setCreatedAt($createdAt)
  {
      $this->createdAt = $createdAt;

      return $this;
  }
  
  public function setCreatedNow()
  {
      return $this->setCreatedAt(new \DateTime());
  }

  /**
   * Get createdAt
   *
   * @return \DateTime 
   */
  public function getCreatedAt()
  {
      return $this->createdAt;
    }
    
    
    
        /**
   * Set updatedAt
   *
   * @param \DateTime $updatedAt
   * @return ?
   */
  public function setUpdatedAt($updatedAt)
  {
      $this->updatedAt = $updatedAt;

      return $this;
  }
  
  public function setUpdatedNow()
  {
      return $this->setUpdatedAt(new \DateTime());
  }

  /**
   * Get updatedAt
   *
   * @return \DateTime 
   */
  public function getUpdatedAt()
  {
      return $this->updatedAt;
    }
}