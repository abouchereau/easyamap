<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait LabelTrait
{
    /**
   * @var string
   *
   * @ORM\Column(name="label", type="string", length=255, nullable=false)
   */
  private $label;
    /**
   * Set label
   *
   * @param string $label
   * @return Farm
   */
  public function setLabel($label)
  {
      $this->label = $label;

      return $this;
  }

  /**
   * Get label
   *
   * @return string 
   */
  public function getLabel()
  {
      return $this->label;
    }
}