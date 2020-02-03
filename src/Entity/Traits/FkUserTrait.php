<?php

 
namespace App\Entity\Traits;
 
use Doctrine\ORM\Mapping as ORM;
 
trait FkUserTrait
{
    /**
     * @var \User
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="fk_user", referencedColumnName="id_user")
     * })
     * @ORM\OrderBy({"isActive" = "DESC", "lastname" = "ASC"})
     */
    private $fkUser;
    

    public function setFkUser(\App\Entity\User $fkUser = null)
    {
        $this->fkUser = $fkUser;

        return $this;
    }

    /**
     * Get fkUser
     *
     * @return \App\Entity\User 
     */
    public function getFkUser()
    {
        return $this->fkUser;
    }
}