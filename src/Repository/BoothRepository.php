<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Booth;

class BoothRepository extends EntityRepository 
{
    const UNLOCK_TIMEOUT = 10;//minutes
    
    public function unlockContract($url, $user) {        
        $tmp = explode('/contrat/',$url);
        $params = $tmp[1];
        //si 2 paramètres dans l'url, user = 2e paramètre
        if (preg_match("#^([0-9]+)\/([0-9]+)$#", $params)) {
            $tmp = explode('/',$params);
            $id_contract = $tmp[0];
            $id_user = $tmp[1];            
        }
        //si 1 paramètre dans l'url, user = current user
        elseif (preg_match("#^([0-9]+)$#", $params)) {
            $id_contract = $params;
            $id_user = $user->getIdUser();            
        }
        else {
            return false;
        }
        
        $params = $id_contract.'-'.$id_user;
        $booth = $this->findOneBy(array('route'=>'contract','params'=>$params));    
        if ($booth != null) {
            $em = $this->getEntityManager();
            $em->remove($booth);
            $em->flush();
        }
        return true;
    }
    
    public function lockContract($id_contract, $id_user, $by_user) {
        $em = $this->getEntityManager();
        $booth = new Booth();
        $booth->setFkUser($by_user);
        $booth->setRoute('contract');
        $booth->setParams($id_contract.'-'.$id_user);
        $booth->setStartedNow();
        try {
            $em->persist($booth);
            $em->flush();
        } catch(\Exception $e) {
            return false;
        }
        return true;
    }
    
    public function isLockedContract($id_contract, $id_user,$current_user) {
        $params = $id_contract.'-'.$id_user;
        $booth = $this->findOneBy(array('route'=>'contract','params'=>$params));
        if ($booth == null || $booth->getFkUser() == $current_user) {
            return false;
        }
        else {
            return true;
        }        
    }
    
    public function unlockOld() {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "delete from booth where started_at < (now() - interval :timeout minute)";
        $stmt = $conn->prepare($sql);        
        return $stmt->execute([":timeout" => self::UNLOCK_TIMEOUT]);
    }
    
    
}