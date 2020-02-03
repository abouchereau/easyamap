<?php
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait IsVisibleDefaultTrueTrait
{
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_visible", type="boolean", nullable=true)
     */
    private $isVisible = true;
    
            /**
     * Set isVisible
     *
     * @param boolean $isVisible
     * @return Contract
     */
    public function setIsVisible($isVisible)
    {
        $this->isVisible = $isVisible;

        return $this;
    }

    /**
     * Get isVisible
     *
     * @return boolean 
     */
    public function getIsVisible()
    {
        return $this->isVisible;
    }
}