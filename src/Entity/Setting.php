<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Setting
 *
 * @ORM\Table(name="setting")
 * @ORM\Entity(repositoryClass="App\Repository\SettingRepository")
 */
class Setting
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    
   /**
     * @var boolean
     *
     * @ORM\Column(name="use_address", type="boolean", nullable=false, )
     */
    private $useAddress = true;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="register_distribution", type="boolean", nullable=false, )
     */
    private $registerDistribution = true;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="cotisation", type="boolean", nullable=false, )
     */
    //private $cotisation = true;

   /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=true)
     */
    private $name;
    
    /**
     * @var string
     *
     * @ORM\Column(name="link", type="string", length=255, nullable=true)
     */
    private $link;

   /**
     * @var string
     *
     * @ORM\Column(name="logo_small_url", type="string", length=255, nullable=true)
     */
    private $logoSmallUrl;    
    
   /**
     * @var string
     *
     * @ORM\Column(name="logo_large_url", type="string", length=255, nullable=true)
     */
    private $logoLargeUrl;
    
    /**
     * @var string
     *
     * @ORM\Column(name="text_register_distribution", type="text", nullable=true)
     */
    private $textRegisterDistribution;
    
   /**
     * @var string
     *
     * @ORM\Column(name="logo_secondary", type="string", length=255, nullable=true)
     */
    private $logoSecondary;
    
    function getId() {
        return $this->id;
    }

    function getUseAddress() {
        return $this->useAddress;
    }

    function getRegisterDistribution() {
        return $this->registerDistribution;
    }

    function getName() {
        return $this->name;
    }

    function getLink() {
        return $this->link;
    }

    function getLogoSmallUrl() {
        return $this->logoSmallUrl;
    }

    function getLogoLargeUrl() {
        return $this->logoLargeUrl;
    }

    function getTextRegisterDistribution() {
        return $this->textRegisterDistribution;
    }
    
   /* function getCotisation() {
        return $this->cotisation;
    }*/
    
    function getLogoSecondary() {
        return $this->logoSecondary;
    }

    function setId($id) {
        $this->id = $id;
        return $this;
    }

    function setUseAddress($useAddress) {
        $this->useAddress = $useAddress;
        return $this;
    }

    function setRegisterDistribution($registerDistribution) {
        $this->registerDistribution = $registerDistribution;
        return $this;
    }

    function setName($name) {
        $this->name = $name;
        return $this;
    }

    function setLink($link) {
        $this->link = $link;
        return $this;
    }

    function setLogoSmallUrl($logoSmallUrl) {
        $this->logoSmallUrl = $logoSmallUrl;
        return $this;
    }

    function setLogoLargeUrl($logoLargeUrl) {
        $this->logoLargeUrl = $logoLargeUrl;
        return $this;
    }

    function setLogoSecondary($logoSecondary) {
        $this->logoSecondary = $logoSecondary;
        return $this;
    }
  /*  
    function setCotisation($cotisation) {
        $this->cotisation = $cotisation;
        return $this;
    }    */
    function setTextRegisterDistribution($textRegisterDistribution) {
        $this->textRegisterDistribution = $textRegisterDistribution;
        return $this;
    }
    
    public function toArray() {
        return array(
            'useAddress' => $this->useAddress,
            'registerDistribution' => $this->registerDistribution,
            'name' => $this->name,
            'link' => $this->link,
            'logoSmallUrl' => $this->logoSmallUrl,
            'logoLargeUrl' => $this->logoLargeUrl,
            'logoSecondary' => $this->logoSecondary,
            'textRegisterDistribution' => $this->textRegisterDistribution
            //'cotisation' => $this->cotisation
        );
    }
    
}