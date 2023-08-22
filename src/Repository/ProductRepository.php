<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class ProductRepository extends EntityRepository
{
  public function findAllForReferent($user)
  {
    return $this->createQueryBuilder('p')
      ->leftJoin('p.fkFarm','f')
      ->leftJoin('App\Entity\Referent','r','WITH','r.fkFarm = f.idFarm')
      ->where('r.fkUser =:user')
      ->orderBy('f.sequence, p.sequence')
      ->setParameter('user', $user)
      ->getQuery()
      ->getResult();
  }
  
  public function findAllOrderByFarm()
  {
    return $this->createQueryBuilder('p')
      ->leftJoin('p.fkFarm','f')
      ->orderBy('f.sequence, p.sequence')
      ->getQuery()
      ->getResult();
  }
  
  public function findForFarm($farm, $filterProductId = []) {
      $qb = $this->createQueryBuilder('p')
      ->where('p.fkFarm =:farm');
      if (count($filterProductId) > 0) {
          $qb->andWhere($qb->expr()->in('p.idProduct', $filterProductId));
      }
      return  $qb->orderBy('p.sequence')
      ->setParameter('farm',$farm)
      ->getQuery()
      ->getResult();
  }
  
  public function findForContract($contract, $referent = null)
  {
    $filterReferent = ($referent != null && !$referent->getIsAdmin());
    $qb = $this->createQueryBuilder('p')
        ->leftJoin('p.fkFarm','f')
        ->leftJoin('App\Entity\ContractProduct','cp','WITH','cp.fkProduct = p.idProduct');
    if ($filterReferent) 
        $qb->leftJoin('App\Entity\Referent','r','WITH','r.fkFarm = f.idFarm');
    $qb->where('cp.fkContract = :contract');
    if ($filterReferent) 
        $qb->andWhere('r.fkUser =:referent');
    $qb->orderBy('f.sequence, p.sequence')
        ->setParameter('contract', $contract);
    if ($filterReferent) 
        $qb->setParameter('referent',$referent);
    return $qb->getQuery()
        ->getResult();
  }
  
  public function canBeDeleted($id_product)
  {
    //on regarde si la ferme apparaît dans d'autres tables
    $conn = $this->getEntityManager()->getConnection();
    $sql = "SELECT COUNT(fk_product) AS nb FROM view_deletable_product WHERE fk_product=:id_product";
    $query = $conn->executeQuery($sql, array('id_product' => $id_product));
    return $query->fetchColumn()==1;
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
            $sql = "truncate temp_product_order";
            $conn->exec($sql);
            $sql = "update product p1, product p2
                set p1.sequence = p2.sequence".$way."5
                where p1.id_product=:id_from
                and p2.id_product=:id_to";
            $stmt = $conn->prepare($sql);
            $v = $stmt->execute(array('id_from'=>$id_from, 'id_to'=>$id_to));           
            $sql = "insert into temp_product_order(id_product)
                select id_product
                from product
                order by sequence";
            $conn->exec($sql);
            $sql = "update product p1, temp_product_order tpo
                set p1.sequence = tpo.id*10
                where p1.id_product = tpo.id_product";
            $conn->exec($sql);
            $conn->commit();
        }
        catch(\Exception $e) {    
            $conn->rollBack();
            return false;
        }
        return true;
    }
    
    
    
    public function restoreSequencing() {
        $conn = $this->getEntityManager()->getConnection();
        $conn->beginTransaction();
        try {
            $sql = "truncate temp_product_order";
            $conn->exec($sql);                   
            $sql = "insert into temp_product_order(id_product)
                select id_product
                from product
                order by sequence";
            $conn->exec($sql);
            $sql = "update product p1, temp_product_order tpo
                set p1.sequence = tpo.id*10
                where p1.id_product = tpo.id_product";
            $conn->exec($sql);
            $conn->commit();
        }
        catch(\Exception $e) {    
            $conn->rollBack();
            return false;
        }
        return true;
    }

    public function getAllProducts() {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "select p.id_product, f.label as farm, p.label, p.unit, p.description, p.base_price, p.ratio, p.is_active, p.is_subscription, p.is_certified, p.created_at, p.updated_at
            from product p
            left join farm f on f.id_farm = p.fk_farm
            where f.is_active = 1
            and p.is_active = 1
            order by f.sequence, p.sequence";
        $r = $conn->query($sql);
        return $r->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProductsMulti($farmsMulti) {
        $conn = $this->getEntityManager()->getConnection();
        $nb_farms = count($farmsMulti);
        for($i = 0; $i < $nb_farms; $i++) {
        $sql = "select db, id_farm, farm_label, id_product, product_label from (";
        foreach($farmsMulti as $farm) {
            $sql .= "SELECT (SELECT name FROM ".$farm['db'].".setting) as db,
            f.id_farm,
            f.label as farm_label,
            p.id_product,
            concat(p.label,' ',p.unit) as product_label,
            f.sequence fseq,
            p.sequence pseq
            from ".$farm['db'].".product p
            left join farm f on f.id_farm = p.fk_farm
            where p.is_active=1";
            if ($i < $nb_farms-1) {
                $sql .= "
UNION";
            }
        }
    }
    $sql .= ") t
    ORDER BY db, fseq, pseq";
        $r = $conn->query($sql);
        $tab = $r->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach($tab as $item) {
           // $key =
           // if (!isset($out[]))
        }

    }
}