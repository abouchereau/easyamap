<?php
 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait EmailTrait
{
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="text", nullable=true)
     */
    private $email;

    /**
     * Set email
     *
     * @param string $email
     * @return Farm
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }
}