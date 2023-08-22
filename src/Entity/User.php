<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Traits\IsActiveDefaultTrueTrait;
use App\Entity\Traits\CreatedAtTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
/**
 * @UniqueEntity(fields="username", message="L'identifiant de connexion est déjà utilisé par un autre adhérent. Merci de bien vouloir en choisir un différent.")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
class User implements UserInterface
{
    use IsActiveDefaultTrueTrait;
    use CreatedAtTrait;
    
    const ROLE_USER = 'ROLE_USER';
    const ROLE_ADHERENT = 'ROLE_ADHERENT';
    const ROLE_REFERENT = 'ROLE_REFERENT';
    const ROLE_ADMIN = 'ROLE_ADMIN';
    const ROLE_FARMER = 'ROLE_FARMER';
    
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $idUser;
    
    /**
     * @var string
     *
     * @ORM\Column(name="firstname", type="string", length=255, nullable=true)
     */
    private $firstname;
    
    /**
     * @var string
     *
     * @ORM\Column(name="lastname", type="string", length=255, nullable=true)
     */
    private $lastname;
    
    /**
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $username;
    
    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    private $password;
    /**
     * @ORM\Column(type="json")
     */
    
    private $roles = [];
    
    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true)
     */
    private $email;
    

    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_admin", type="boolean", nullable=false)
     */
    private $isAdmin;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_adherent", type="boolean", nullable=false)
     */
    private $isAdherent;
    
/**
    * @ORM\ManyToMany(targetEntity="App\Entity\Farm")
     * @JoinTable(name="referent",
     *      joinColumns={@JoinColumn(name="fk_user", referencedColumnName="id_user")},
     *      inverseJoinColumns={@JoinColumn(name="fk_farm", referencedColumnName="id_farm")}
     *      )
     **/

     private $farms;
     
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_connection", type="date", nullable=true)
     */
     private $lastConnection;
     
    /**
     * @var string
     *
     * @ORM\Column(name="tel1", type="string", length=255, nullable=true)
     */
    private $tel1;
    
    /**
     * @var string
     *
     * @ORM\Column(name="tel2", type="string", length=255, nullable=true)
     */
    private $tel2;
    
    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     */
    private $address;
    
    /**
     * @var string
     *
     * @ORM\Column(name="zipcode", type="string", length=5, nullable=true)
     * @Assert\Length(      
     *      max = 5,     
     *      maxMessage = "Le code postal doit faire 5 caractères"
     * )
     */
    private $zipcode;
    
    /**
     * @var string
     *
     * @ORM\Column(name="town", type="string", length=255, nullable=true)
     */
    private $town;

    public function getIdUser(): ?int
    {
        return $this->idUser;
    }

     /**
     * Set firstname
     *
     * @param string $firstname
     * @return User
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }
    
   /**
     * Set lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    
    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
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
    /**
     * @see UserInterface
     */

    public function getRoles()
    {
        $session = new Session();
        $roles = array(); 
        
        if ($session->has('roles')) {
            $rolesStr = $session->get('roles');            
            if (strpos($rolesStr,'1') !== false)
                $roles[] = self::ROLE_USER;
            if (strpos($rolesStr,'2') !== false)
                $roles[] = self::ROLE_ADHERENT;
            if (strpos($rolesStr,'3') !== false)
                $roles[] = self::ROLE_REFERENT;
            if (strpos($rolesStr,'4') !== false)
                $roles[] = self::ROLE_FARMER;
            if (strpos($rolesStr,'5') !== false) {
                $roles[] = self::ROLE_ADMIN;                
                $roles[] = self::ROLE_REFERENT;               
            }
        }
        //$roles = [self::ROLE_USER,self::ROLE_ADHERENT];//TODP
       //die(print_r($roles,1));
        return $roles;
    }
    
    public function hasRole($role) {
        $roles = $this->getRoles();
        return in_array($role,$roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

   /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }
    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    public function getFarms()
    {
      return $this->farms;
    }
    
    public function isReferent()
    {
      return count($this->getFarms()) > 0;
    }
    
    
    public function __toString()
    {
      return $this->lastname.' '.$this->firstname;
    }
    

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed for apps that do not check user passwords
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
    
     public function isEnabled()
    {
        return $this->isActive;
    }
    
               /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsAdmin()
    {        
        return $this->isAdmin;
    }
    
    /**
     * Set isAdherent
     *
     * @param boolean $isAdherent
     * @return User
     */
    public function setIsAdherent($isAdherent)
    {
        $this->isAdherent = $isAdherent;

        return $this;
    }

    /**
     * Get isAdherent
     *
     * @return boolean 
     */
    public function getIsAdherent()
    {        
        return $this->isAdherent;
    }
    
    
    /**
     * Set lastConnection
     *
     * @param \DateTime $lastConnection
     * @return User
     */
    public function setLastConnection($lastConnection)
    {
        $this->lastConnection = $lastConnection;

        return $this;
    }
    
    function getLastConnection() {
        return $this->lastConnection;
    }
    
    function getTel1() {
    return $this->tel1;
    }

     function getTel2() {
    return $this->tel2;
    }

     function getAddress() {
    return $this->address;
    }

     function getZipcode() {
    return $this->zipcode;
    }

     function getTown() {
    return $this->town;
    }

     function setTel1($tel1) {
    $this->tel1 = $tel1;
    return $this;
    }

     function setTel2($tel2) {
    $this->tel2 = $tel2;
    return $this;
    }

     function setAddress($address) {
    $this->address = $address;
    return $this;
    }

     function setZipcode($zipcode) {
    $this->zipcode = $zipcode;
    return $this;
    }

     function setTown($town) {
    $this->town = $town;
    return $this;
    }

}
