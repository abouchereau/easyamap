<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\EquatableInterface;
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
class User implements UserInterface, EquatableInterface
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
     * @var ?string plain password. Do not store it !!
     */
    private $plainPassword;

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
        $sRoles = $this->roles;// Charge les rôles précédents
        $sRoles[] = 'ROLE_USER';// garantie que l'utilisateur est au moins un utilisateur au sens de symfony

        if ($session->has('roles')) {// charge la partie des rôles stockés en session
            $rolesStr = $session->get('roles');            
            if (strpos($rolesStr,'1') !== false) {
                $sRoles[] = self::ROLE_USER;
            }
            if (strpos($rolesStr,'2') !== false) {
                $sRoles[] = self::ROLE_ADHERENT;
            }
            if (strpos($rolesStr,'3') !== false) {
                $sRoles[] = self::ROLE_REFERENT;
            }
            if (strpos($rolesStr,'4') !== false) {
                $sRoles[] = self::ROLE_FARMER;
            }
            if (strpos($rolesStr,'5') !== false) {
                $sRoles[] = self::ROLE_ADMIN;
                $sRoles[] = self::ROLE_REFERENT;
            }
        }

        return $sRoles;
    }
    
    public function hasRole($role) {
        $sRoles = $this->getRoles();
        return in_array($role,$sRoles);
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
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }


    /**
     * @return string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     */
    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }
    
    public function getFarms()
    {
      return $this->farms;
    }
    
    public function isReferent(): bool
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
        //No need to implements salt with bcrypt or sodium, salt is included in password
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // clear plain text password (must be called after authentication)
        $this->setPlainPassword(null);
    }

     public function isEnabled(): ?bool
     {
        return $this->isActive;
    }
    
     /**
     * Set isAdmin
     *
     * @param boolean $isAdmin
     * @return User
     */
    public function setIsAdmin(bool $isAdmin): User
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isAdmin
     *
     * @return boolean 
     */
    public function getIsAdmin(): ?bool
    {        
        return $this->isAdmin;
    }
    
    /**
     * Set isAdherent
     *
     * @param boolean $isAdherent
     * @return User
     */
    public function setIsAdherent(bool $isAdherent): User
    {
        $this->isAdherent = $isAdherent;

        return $this;
    }

    /**
     * Get isAdherent
     *
     * @return boolean 
     */
    public function getIsAdherent(): ?bool
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

    public function isEqualTo(UserInterface $user)
    {

        if (! $user instanceof User) {
            return false;
        }

        if ($user->getIdUser() !== $this->getIdUser() ) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        return true;
    }
}
