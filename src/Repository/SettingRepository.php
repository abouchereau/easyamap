<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;


class SettingRepository extends EntityRepository 
{
    const FILEPATH = '/../../var/cache/setting_env.json';
    const MANIFEST_PATH = '/../../public/manifest/env.json';
    protected $tab;
    
    public function updateCache($env) {
        $entity = $this->findOneBy(array(),array('id'=>'DESC'));
        $json = json_encode($entity->toArray(), JSON_INVALID_UTF8_SUBSTITUTE);
        file_put_contents($this->filepath($env), $json);
        $this->updateManifest($env,true);
    }    
    
    public function getFromCache($env) {
        if($this->tab == null) {
            if(!file_exists($this->filepath($env))) {
                $this->updateCache($env);
            }
            $this->tab = json_decode(file_get_contents($this->filepath($env)),true);
        }
        return $this->tab;
    }
    
    public function get($key,$env) {
        $tab = $this->getFromCache($env);
        return $tab[$key];
    }
    
    private function filepath($env) {
        return __DIR__.str_replace('env',$env,self::FILEPATH);
    }
    
    private function manifestpath($env) {
        return __DIR__.str_replace('env',$env,self::MANIFEST_PATH);
    }

    private function getUrlFromEnv($env) {
        require __DIR__.'/../../config/url2env.php';
        foreach($url2env as $key => $value) {
            if($env == $value[0]) {
                return $key;
            }
        }
        return "";
    }
            
    public function updateManifest($env, $force) {
        $url = $this->getUrlFromEnv($env);
        $setting = $this->getFromCache($env);
        $path = $this->manifestpath($env);
        if(!file_exists($path) || $force) {
            $json = '{
  "id": "https://'.$url.'"
  "short_name": "Easyamap",
  "name": "Easyamap - '.$setting['name'].',
  "description": "Logiciel de commandes pour l\'AMAP : '.$setting['name'].'",
    "icons": [
    {
      "src": "/images/logo-192.png",
      "type": "image/png",
      "sizes": "192x192"
    },
    {
      "src": "/images/logo-512.png",
      "type": "image/png",
      "sizes": "512x512"
    }
  ],
  "start_url": "/",
  "background_color": "#FFFFFF",
  "display": "standalone",
  "scope": "/",
  "theme_color": "#4DB748",
  "orientation":"any",    
  "form_factors": ["phone", "desktop"],
  "screenshots": [
    {
      "src": "/images/screenshot.png",
      "sizes": "792x830",
      "type": "image/png"
    }
  ]
}
';
            try {
                file_put_contents($path, $json);
            } catch(\Exception $e) {
                
            }
        }
    }

    public function getBackups($db_name) {
        $files = scandir(__DIR__."/../../../../backup");
        $out = [];
        foreach($files as $file) {
            if (strpos($file,$db_name)===0) {
                $out[] = $file;
            }
        }
        $out = array_reverse($out);
        return $out;
    }

    public function getAllDatabases() {
        require __DIR__.'/../../config/url2env.php';
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SHOW DATABASES 
            WHERE `Database` LIKE 'amap_%' 
            AND `Database` NOT LIKE 'amap_test%'
            AND `Database` NOT LIKE 'amap_tmp%'
            AND `Database` not in('amap_init', 'amap_admin', 'amap_corresp')";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $allDb = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        $sqlTab = [];
        foreach($allDb as $db) {
            $ndd = "";
            foreach ($url2env as $nom_domaine => $env) {
                if ($env[0] == str_replace("amap_","",$db)) {
                    $ndd = $nom_domaine;
                    break;
                }
            }
            $sqlTab[] = "select '".$db."' as db, '".$ndd."' as nom_domaine, name from ".$db.".setting";
        }
        $sql = implode(" UNION ALL ", $sqlTab);
        $stmt = $conn->prepare($sql);   
        $stmt->execute();
        $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $res;
    }

       
}
