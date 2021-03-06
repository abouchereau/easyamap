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
  
  public function findForFarm($farm) {
      return $this->createQueryBuilder('p')
      ->where('p.fkFarm =:farm')
      ->orderBy('p.sequence')
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
}