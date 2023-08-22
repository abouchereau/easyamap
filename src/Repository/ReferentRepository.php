<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;


class ReferentRepository extends EntityRepository 
{

    public function findFarms($user) {        
        if (!$user->getIsAdmin() && $user->isReferent()) {
            return array();
        }
        $sql = "select fk_farm
            from referent";
        if(!$user->getIsAdmin()) {
            $sql .= " where fk_user=:id_user";
        }
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('id_user'=>$user->getIdUser()));
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}