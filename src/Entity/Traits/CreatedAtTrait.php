<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait CreatedAtTrait
{
    #[ORM\Column(name: 'created_at', type: 'date', nullable: true)]
    private $createdAt;
    
        
    /**
     * Set date
     *
     * @param \DateTime $createdAt
     * @return 
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }    
    

}