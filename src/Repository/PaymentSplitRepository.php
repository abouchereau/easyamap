<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\PaymentSplit;
use App\Util\Utils;

class PaymentSplitRepository extends EntityRepository 
{
     public function addMany($description, $split, $payment) {
          $em = $this->getEntityManager();
          foreach($description as $i => $info) {
            $ps = new PaymentSplit();
            $ps->setAmount(Utils::numerize($split[$i]));
            $ps->setDate(\DateTime::createFromFormat('Y-m-d', $info[0]));
            $ps->setFkPayment($payment);
            try {
                $em->persist($ps);
                $em->flush(); 
            } catch(Exception $e) {
                return $e->getMessage();
            }          
        }
        return true;
     }
     
     public function removeFromPayment($payment) {
        $em = $this->getEntityManager();
        $ps = $this->findBy(array('fkPayment'=>$payment));
        foreach ($ps as $each) {
            try {
                $em->remove($each);
                $em->flush(); 
            } catch(Exception $e) {
                return $e->getMessage();
            }  
        }
        return true;
     }
     
     public function history($user, $farm, $from, $to) {
        if ($from != null) {
            $from = \DateTime::createFromFormat('Y-m-d', $from);
        }
        if ($to != null) {
            $to = \DateTime::createFromFormat('Y-m-d', $to);
        }
        
        $qb = $this->createQueryBuilder('ps')
         ->leftJoin('ps.fkPayment','p')
         ->where('p.fkUser = :user')
         ->andWhere('p.fkFarm = :farm');      
          if ($from != null) {
             $qb->andWhere('ps.date >= :from');
          }
          if ($to != null) {
             $qb->andWhere('ps.date <= :to');
          }
         $qb->addOrderBy('p.receivedAt','DESC')
         ->addOrderBy('p.idPayment','DESC')
         ->setParameter('user', $user)
         ->setParameter('farm', $farm);
         if ($from != null) {
             $qb->setParameter('from', $from);
         }
         if ($to != null) {
             $qb->setParameter('to', $to);
         }
         return $qb->getQuery()
         ->getResult();
     }
}