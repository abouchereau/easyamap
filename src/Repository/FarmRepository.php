<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Session\Session;

class FarmRepository extends EntityRepository 
{
    public function findAllOrderByLabel($user)
    {
        $session = new Session();
        $role = User::ROLE_REFERENT;
        if ($session->has('role')) {
            $role = $session->get('role');
        }
        $qb = $this->createQueryBuilder('f');
        if ($role == User::ROLE_FARMER && !$user->hasRole(User::ROLE_ADMIN)) {
            $qb->where('f.fkUser = :user')
                ->setParameter('user', $user);
        }
        elseif($role == User::ROLE_REFERENT && !$user->hasRole(User::ROLE_ADMIN)) {
            $qb->leftJoin('App\Entity\Referent','r','WITH','r.fkFarm = f.idFarm')
            ->where('r.fkUser = :user')
            ->setParameter('user', $user);
        }

        $qb->addOrderBy('f.isActive', 'DESC')
         ->addOrderBy('f.sequence');
         return $qb->getQuery()->getResult();
    }
    
    public function findAllOrderByLabel2()
    {
       $qb = $this->createQueryBuilder('f');
       $qb->addOrderBy('f.isActive', 'DESC')
         ->addOrderBy('f.sequence');
         return $qb->getQuery()->getResult();
    }
    
    public function canBeDeleted($id_farm)
    {
      //on regarde si la ferme apparaît dans d'autres tables
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT COUNT(fk_farm) AS nb FROM view_deletable_farm WHERE fk_farm=:id_farm";
      $query = $conn->executeQuery($sql, array('id_farm' => $id_farm));
      return $query->fetchColumn()==1;
    }
    
    public function findForReferent($user)
    {
       return $this->createQueryBuilder('f')
            ->leftJoin('App\Entity\Referent','r','WITH','r.fkFarm = f.idFarm')
            ->where('r.fkUser = :user')
            ->setParameter('user', $user)
            ->getQuery()->getResult();
    }
    
    public function findForContract($id_contract) {
        $sql = "select distinct(fk_farm), 1
            from farm f
            left join product p on p.fk_farm = f.id_farm
            left join contract_product cp on cp.fk_product = p.id_product
            where cp.fk_contract=:id_contract";
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id_contract' => $id_contract]);
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
    

    
    /**
   * à partir de l'ordre courant, on récrée un ordre de 10 en 10
   * @return boolean
   * @throws \Exception
   */
  
    public function restoreSequencing() {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $sql = "truncate temp_farm_order";
            $conn->exec($sql);                   
            $sql = "insert into temp_farm_order(id_farm)
                select id_farm
                from farm
                order by sequence";
            $conn->exec($sql);
            $sql = "update farm p1, temp_farm_order tpo
                set p1.sequence = tpo.id*10
                where p1.id_farm = tpo.id_farm";
            $conn->exec($sql);
            $conn->commit();
        }
        catch(\Exception $e) {    
            $conn->rollBack();
            return false;
        }
        return true;
    }  
    
  public function changeOrder($id_from, $id_before, $id_after) {
        //on choisit d'abord l'id_before s'il existe
        $id_to = $id_before;
        $way = '+';
        if ($id_before == 0) {
            $id_to = $id_after;
            $way = '-';
        }
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $sql = "truncate temp_farm_order";
            $conn->exec($sql);
            $sql = "update farm p1, farm p2
                set p1.sequence = p2.sequence".$way."5
                where p1.id_farm=:id_from
                and p2.id_farm=:id_to";
            $stmt = $conn->prepare($sql);
            $v = $stmt->execute(array('id_from'=>$id_from, 'id_to'=>$id_to));           
            $sql = "insert into temp_farm_order(id_farm)
                select id_farm
                from farm
                order by sequence";
            $conn->exec($sql);
            $sql = "update farm p1, temp_farm_order tpo
                set p1.sequence = tpo.id*10
                where p1.id_farm = tpo.id_farm";
            $conn->exec($sql);
            $conn->commit();
        }
        catch(\Exception $e) {    
            $conn->rollBack();
            return false;
        }
        return true;
    }
    
    protected $payment_types = array();
    
    public function getPaymentTypes($id_farm) {
        if (!isset($this->payment_types[$id_farm])) {
            //on récupère tout
            $em = $this->getEntityManager();
            $conn = $em->getConnection();
            $sql = "select f.id_farm, fpt.fk_payment_type
                    from farm f
                    left join farm_payment_type fpt on fpt.fk_farm = f.id_farm
                    order by fpt.fk_payment_type";
             $r = $conn->query($sql);
             $this->payment_types = $r->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
        }
        return $this->payment_types[$id_farm];
    }
    
    protected $payment_freqs = array();
    
    public function getPaymentFreqs($id_farm) {
        if (!isset($this->payment_freqs[$id_farm])) {
            //on récupère tout
            $em = $this->getEntityManager();
            $conn = $em->getConnection();
            $sql = "select f.id_farm, fpf.fk_payment_freq
                    from farm f
                    left join farm_payment_freq fpf on fpf.fk_farm = f.id_farm
                    order by fpf.fk_payment_freq";
             $r = $conn->query($sql);
             $this->payment_freqs = $r->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
        }
        return $this->payment_freqs[$id_farm];
    }

    public function getAllFarms() {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select id_farm, label, product_type, check_payable_to, description, link 
            from farm
            where is_active=1
            order by sequence";
        $r = $conn->query($sql);
        return $r->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getFarmsMulti($farms, $db) {
        $conn = $this->getEntityManager()->getConnection();
        $farms_id = [];
        foreach($farms as $farm) {
            $farms_id[] = $farm->getIdFarm();
        }

        $sql = "select distinct db, id_farm, is_cur_db from (
                    select c2.db, c2.id_farm, 0 as is_cur_db
                    from amap_corresp.farm_corresp c2
                    left join amap_corresp.farm_corresp c1 on c1.id_farm_entity = c2.id_farm_entity
                    WHERE c1.id_farm IN(" . implode(",", $farms_id) . ") 
                    and c1.db=:db
                    and c2.db<>:db
                    union
                    select '" . $db . "' as db, id_farm, 1 as is_cur_db
                    from " . $db . ".farm
                    WHERE id_farm IN(" . implode(",", $farms_id) . ")
                    ) t ORDER BY db, id_farm";
        $r = $conn->executeQuery($sql, array('db' => $db));
        return $r->fetchAll(\PDO::FETCH_ASSOC);
    }
}