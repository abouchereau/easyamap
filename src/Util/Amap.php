<?php
namespace App\Util;

class Amap {
    
    protected $name;//nom pour la bdd et les fichiers de param
    protected $debug;
    
    protected $sites;
    protected $presentationUrl = 'https://www.easyamap.fr/';
    
    public function __construct($sites) {
        $this->sites = $sites;
        $this->initFromUrl();
    }

    public function getName() {
        return $this->name;
    }
    
    public function getDebug() {
        return $this->debug;
    }
    
    protected function getFullLink() {
        return $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
    
    protected function initFromUrl() {
        $link = $this->getFullLink();
        if (strpos($link, 'la-riche-en-bio')!==false) {
            $this->redirect('https://la-riche.easyamap.fr');			
        }   
        if (strpos($link, 'amap-de-la-plage')!==false) {
            $this->redirect('https://faverolles.easyamap.fr');			
        }
        if (strpos($link, 'attikcreation')!==false) {
            $this->redirect('https://faverolles.easyamap.fr');			
        }
        if (strpos($link, 'amappdelasalle')!==false) {
            $this->redirect('https://cassardieres.easyamap.fr');
        }
        foreach($this->sites as $domain => $info) {
            if (strpos($link,$domain) !== false) {
                $this->name = $this->sites[$domain][0];
                $this->debug = $this->sites[$domain][1];
                return;
            }
        }
        $this->goToPresentationSite();
    }
    
    protected function goToPresentationSite()
    {
        $this->redirect($this->presentationUrl);
    }
    
    public function checkMaintenance($ip) {
        if(MAINTENANCE && $_SERVER['REMOTE_ADDR'] != $ip && strpos($_SERVER['REQUEST_URI'],'maintenance')===false) {
			die("<!doctype html><head></head><body><div style='text-align:center;width:100%;'>Le site est actuellement en maintenance.<br />
Merci de revenir dans quelques instants.<br /><img src='https://www.easyamap.fr/images/logo-easy-amap-160.png' /></div></body></html>");
            $this->redirect('/maintenance');
        }
    }
    
    protected function redirect($url)
    {
        header('Location: '.$url);
        exit;
    }

    static public function isEasyamapMainServer() {
        return strpos($_SERVER['HTTP_HOST'], 'easyamap.fr')!==false;
    }
}

