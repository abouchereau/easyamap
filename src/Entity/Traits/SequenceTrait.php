<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait SequenceTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="sequence", type="integer")
     */
    private $sequence;

    /**
     * Set sequence
     *
     * @param string $sequence
     */
    public function setSequence($sequence)
    {       
        $this->sequence = $sequence;

        return $this;
    }
    
    /**
     * Mets l'ordre en dernier
     * appeler "restoreSequencing" ensuite pour qu'il soit à +10
     *
     * @param string $sequence
     */
    
    public function setSequenceAtEnd() {
        $this->sequence = 100000;

        return $this;
    }

    /**
     * Get sequence
     *
     * @return string 
     */
    public function getSequence()
    {
        return $this->sequence;
    }
}