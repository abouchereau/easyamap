<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\Purchase;

class PurchaseRepository extends EntityRepository
{
  public function getProductsNextDistributionByUser($user, $date, $limit)
  {
    $distris = $this->getNextDistributions($date, $limit);
    $conn = $this->getEntityManager()->getConnection();
    $sql = "SELECT pd.fk_distribution AS id_distribution, 
        pu.quantity, 
        CONCAT(ifnull(p.label,''), 
        ' ', 
        ifnull(p.unit,'')) AS produit
      FROM product_distribution pd
      LEFT JOIN purchase pu ON pu.fk_product_distribution = pd.id_product_distribution
      LEFT JOIN product p   ON p.id_product = pd.fk_product
      LEFT JOIN farm f      ON f.id_farm = p.fk_farm
      LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution
      WHERE pu.fk_user = :id_user
      AND pd.fk_distribution IN (".implode(', ',  array_keys($distris)).")
      GROUP BY pd.fk_distribution, p.id_product
      ORDER BY d.date, f.sequence, p.sequence";
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id_user' => $user->getIdUser()]);
    $products = $stmt->fetchAll(\PDO::FETCH_GROUP);
    $tab = array();
    foreach($distris as $id => $dateStr)
    {
      $tab[$dateStr] = array('date' => \DateTime::createFromFormat('Y-m-d', $dateStr));
      if (isset($products[$id]))
        $tab[$dateStr]['produits'] = $products[$id];
    }
    return $tab;
  }
  
  public function getProductsNextDistributionByFarm($farm, $date, $limit)
  {
   $distris = $this->getNextDistributions($date, $limit);
   $conn = $this->getEntityManager()->getConnection();
   $sql = "SELECT pd.fk_distribution AS id_distribution, IFNULL(SUM(pu.quantity),0) AS quantity, CONCAT(ifnull(p.label,''), ' ', ifnull(p.unit,'')) AS produit
      FROM product_distribution pd
      LEFT JOIN purchase pu ON pu.fk_product_distribution = pd.id_product_distribution
      LEFT JOIN product p   ON p.id_product = pd.fk_product
      LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution
      WHERE p.fk_farm=:id_farm
      AND pd.fk_distribution IN (".implode(', ',  array_keys($distris)).")
      GROUP BY pd.fk_distribution, p.id_product
      ORDER BY d.date, p.sequence";
      $stmt = $conn->prepare($sql);
      $stmt->execute(['id_farm' => $farm->getIdFarm()]);
    $products = $stmt->fetchAll(\PDO::FETCH_GROUP);
    $tab = array();
    foreach($distris as $id => $dateStr)
    {
      $tab[$dateStr] = array('date' => \DateTime::createFromFormat('Y-m-d', $dateStr));
      if (isset($products[$id]))
        $tab[$dateStr]['produits'] = $products[$id];
    }
   // die(print_r($distris,1).' - '.print_r($tab,1));
    return $tab;
  }
    
  
  protected function getNextDistributions($date, $limit)
  {
    $conn = $this->getEntityManager()->getConnection();
    $sql = "SELECT id_distribution, date 
      FROM distribution 
      WHERE date >= :date
      LIMIT :limit";
      $stmt = $conn->prepare($sql);
      $stmt->execute(['date' => $date,'limit' => $limit]);
    return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
  }
  
  public function getPurchase($ids_distributions, $id_user,$contract)
  {
    $conn = $this->getEntityManager()->getConnection();
    $sql = "SELECT pd.id_product_distribution, p.quantity
            FROM purchase p
            LEFT JOIN product_distribution pd ON p.fk_product_distribution = pd.id_product_distribution
            LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution
            WHERE d.id_distribution IN (".implode(',',$ids_distributions).")
            AND p.fk_user=:id_user
            AND p.fk_contract=:id_contract";        
      $stmt = $conn->prepare($sql);
      $stmt->execute(['id_user' => $id_user,'id_contract' => $contract->getIdContract()]);
     return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
  }
  
  public function emptyContract($id_contract, $id_user, $referent=null)
  {
   /* $list = array();
    foreach ($product_distributions as $product_distribution)
    {
      $list[] = $product_distribution['id_product_distribution'];
    }*/
    //$list = array_column($product_distributions,'id_product_distribution');// PHP 5.5
    $conn = $this->getEntityManager()->getConnection();
    $params = [];
    if ($referent == null || $referent->getIsAdmin()) {
        $sql = "DELETE FROM purchase
            WHERE fk_user=:id_user
            AND fk_contract=:id_contract";
            $params = ['id_user' => $id_user,'id_contract' => $id_contract];
    } else {
        $sql = "DELETE p FROM purchase p
            left join product_distribution pd on pd.id_product_distribution = p.fk_product_distribution
            left join product pr on pr.id_product = pd.fk_product
            left join farm f on f.id_farm = pr.fk_farm
            left join referent r on r.fk_farm = f.id_farm
            WHERE p.fk_user=:id_user
            and r.fk_user=:id_referent
            AND p.fk_contract=:id_contract";        
            $params = ['id_user' => $id_user,'id_contract' => $id_contract,"id_referent"=>$referent->getIdUser()];
    }
      $stmt = $conn->prepare($sql);
      $nb = $stmt->execute($params);
    return $nb;
  }
  
  public function add($user, $tab, $contract)
  {
    $em = $this->getEntityManager();
    $ids_purchase = array();
    foreach ($tab as $id_product_distribution => $quantity)
    {
      try 
      {
        $productDistribution = $em->getRepository('App\Entity\ProductDistribution')->find($id_product_distribution);
      }
      catch(Exception $e)
      {
        return false;
      }
      $purchase = new Purchase();
      $purchase->setFkUser($user)
        ->setFkProductDistribution($productDistribution)
        ->setFkContract($contract)
        ->setQuantity($quantity);
      try 
      {
       $em->persist($purchase);
       $em->flush();//à enlever pour la transaction ?       
      }
      catch(Exception $e)
      {
        return false;
      }
      $farm_id = $purchase->getFkProductDistribution()->getFkProduct()->getFkFarm()->getIdFarm();
      if (!isset($ids_purchase[$farm_id])) {
          $ids_purchase[$farm_id] = array();
      }
      $ids_purchase[$farm_id][] = $purchase->getIdPurchase();
    }
    return $ids_purchase;
  }
  
  public function getProductsToShip($dates, $farms=null)
  {
      if ($farms != null) {
          $farms_id = array();
          foreach ($farms as $farm) {
              $farms_id[] = $farm->getIdFarm();
          }
      }
      
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT * FROM (
SELECT 
f.label AS entity,
d.date AS date,
pr.fk_farm AS fk_farm, 
sum(pu.quantity) AS nb, 
concat(ifnull(pr.label,''),' ',ifnull(pr.unit,'')) AS produit, 
f.sequence as f_seq, 
pr.sequence as pr_seq,
d2.date AS date_shift,
0 is_shift
from purchase pu 
left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
left join distribution d on d.id_distribution = pd.fk_distribution 
left join product pr on pr.id_product = pd.fk_product 
left join farm f on f.id_farm = pr.fk_farm
left join distribution d2 ON d2.id_distribution = pd.fk_distribution_shift 
WHERE d.date IN ('".implode("','",$dates)."')";
      if($farms != null) {
        $sql .= "AND pr.fk_farm IN (".implode(',',$farms_id).") ";
      }
$sql .= " group by f.label, d.date, pr.fk_farm, pr.id_product, f.sequence, pr.sequence, d2.date
UNION
SELECT 
f.label AS entity,
d2.date AS date,
pr.fk_farm AS fk_farm, 
sum(pu.quantity) AS nb, 
concat(ifnull(pr.label,''),' ',ifnull(pr.unit,'')) AS produit, 
f.sequence as f_seq, 
pr.sequence as pr_seq,
d.date AS date_shift,
1 is_shift
from purchase pu 
left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
left join distribution d on d.id_distribution = pd.fk_distribution 
left join product pr on pr.id_product = pd.fk_product 
left join farm f on f.id_farm = pr.fk_farm
left join distribution d2 ON d2.id_distribution = pd.fk_distribution_shift 
WHERE pu.fk_product_distribution IS NOT NULL
AND d2.date IN ('".implode("','",$dates)."')";
      if($farms != null) {
        $sql .= "AND pr.fk_farm IN (".implode(',',$farms_id).") ";
      }
$sql .= "
group by f.label, d.date, pr.fk_farm, pr.id_product, f.sequence, pr.sequence, d2.date
) v
ORDER BY v.f_seq, v.DATE, v.pr_seq, v.is_shift";

        $r = $conn->query($sql);
        $tab = $r->fetchAll(\PDO::FETCH_GROUP);
        return $this->fetchGroupTwoLevels($tab);
  }     
  
  public function getProductsToRecover($dates, $id_user=null)
  {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT * FROM (
SELECT 
	CONCAT(ifnull(u.lastname,''),'<br>',ifnull(u.firstname,'')) AS entity,    
    0 is_shift,
    pd.id_product_distribution,
	d.date AS date,
    pu.fk_user AS fk_user,
    pu.quantity AS nb,
    concat(ifnull(pr.label,''),' ',ifnull(pr.unit,'')) AS produit,
    pr.sequence as pr_seq,
    d2.date AS date_shift
    from purchase pu 
    left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution 
    left join distribution d on d.id_distribution = pd.fk_distribution 
    left join distribution d2 ON d2.id_distribution = pd.fk_distribution_shift
    left join product pr on pr.id_product = pd.fk_product
	LEFT JOIN user u ON u.id_user = pu.fk_user
	WHERE d.date IN ('".implode("','",$dates)."')
    AND (pu.fk_user=:id_user OR :id_user is null)
UNION  
SELECT     
	CONCAT(ifnull(u.lastname,''),'<br>',ifnull(u.firstname,'')) AS entity,	 
    1 is_shift,
    pd.id_product_distribution,
	d2.date AS date,
    pu.fk_user AS fk_user,
    pu.quantity AS nb,
    concat(ifnull(pr.label,''),' ',ifnull(pr.unit,'')) AS produit,
    pr.sequence as pr_seq,
    d.date AS date_shift
    from purchase pu 
    left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution 
    left join distribution d on d.id_distribution = pd.fk_distribution 
    left join distribution d2 ON d2.id_distribution = pd.fk_distribution_shift
    left join product pr on pr.id_product = pd.fk_product
	LEFT JOIN user u ON u.id_user = pu.fk_user
	WHERE d2.date IN ('".implode("','",$dates)."')
    AND (pu.fk_user=:id_user OR :id_user is null)
	) v
    ORDER BY v.entity, v.date, v.pr_seq";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id_user'=>$id_user]);
        $tab = $stmt->fetchAll(\PDO::FETCH_GROUP);
        return $this->fetchGroupTwoLevels($tab);
  }
  
  protected function fetchGroupTwoLevels($tab)
  {
      foreach ($tab as $entity => $infos)
      {
          $newInfo = array();
          foreach($infos as $info)
          {
              if (!isset($newInfo[$info['date']]))
                  $newInfo[$info['date']] = [];
              $newInfo[$info['date']][] = ['nb'=>$info['nb'],'produit' => $info['produit'],'date_shift' => $info['date_shift'],'is_shift' => $info['is_shift']];
          }
          $tab[$entity] = $newInfo;
      }
      return $tab;
  }
  
  public function getFarmsFromPurchases($ids_purchase) {//à tester
       $conn = $this->getEntityManager()->getConnection();
        $sql = "SELECT 
            distinct(f.id_farm)
            FROM purchase p
            LEFT JOIN product_distribution pd ON pd.id_product_distribution = p .fk_product_distribution
            LEFT JOIN product pr ON pr.id_product = pd.fk_product
            LEFT JOIN farm f ON f.id_farm = pr.fk_farm
            left join view_payment_purchase v1 on v1.fk_purchase = p.id_purchase
            left join payment pa on pa.id_payment = v1.fk_payment
            WHERE p.id_purchase IN (".implode(',',$ids_purchase).")
            and pa.received=0";//TODO requete preparee
        $r = $conn->query($sql);
        return$r->fetchAll(\PDO::FETCH_COLUMN);
  }
  
  public function getAmountByDistribution($ids_purchase) {
      $conn = $this->getEntityManager()->getConnection();
      
      $sql = "SELECT
          t.id_farm,
          t.date,
          ROUND(SUM(t.prix_total),2) AS amount,
	  SUM(t.has_ratio) AS has_ratio
          FROM (
            SELECT 
            p.id_purchase,
            d.date,
            f.id_farm,
            CASE WHEN pr.ratio IS NULL THEN 0 ELSE 1 END AS has_ratio,
            case when pr.ratio is null then pd.price*p.quantity else 0 end AS prix_total 
            FROM purchase p
            LEFT JOIN product_distribution pd ON pd.id_product_distribution = p .fk_product_distribution
            LEFT JOIN product pr ON pr.id_product = pd.fk_product
            LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution
            LEFT JOIN farm f ON f.id_farm = pr.fk_farm
            WHERE p.id_purchase IN (".implode(',',$ids_purchase).")
) AS t
          GROUP BY t.id_farm, t.date, t.has_ratio";//TODO requete preparee
      
      $r = $conn->query($sql);
      return $r->fetchAll(\PDO::FETCH_ASSOC);
  }
  
  public function userPurchaseMonth() {
      $conn = $this->getEntityManager()->getConnection();
      $sql = 'SELECT
                u.lastname,
                date_format(d.date, "%Y%m")
                from user u
                left join purchase p on u.id_user = p.fk_user
                left join product_distribution pd on pd.id_product_distribution = p.fk_product_distribution
                left join distribution d on d.id_distribution = pd.fk_distribution
                where u.is_active=1
                group by u.lastname, date_format(d.date, "%Y%m")
                order by u.lastname';
      $r = $conn->query($sql);
      return $r->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_COLUMN);
  }
  
  public function getPurchaseCountSince($date, $products, $user) {
        $id_products = [];
        foreach($products as $product) {
            $id_products[] = $product->getIdProduct();
        }
      $conn = $this->getEntityManager()->getConnection();
      $sql = "select pr.label, pr.unit, sum(pu.quantity) as quantity
                from purchase pu
                left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
                left join distribution dis on dis.id_distribution = pd.fk_distribution
                left join product pr on pr.id_product = pd.fk_product
                where pd.fk_product in(".implode(',',$id_products).")
                and pu.fk_user = :id_user
                and dis.date > :date
                group by pd.fk_product";//TODO requete preparee
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('id_user'=>$user->getIdUser(), 'date'=>$date->format('Y-m-d')));
       return $stmt->fetchAll(\PDO::FETCH_ASSOC); 
  }
  
  public function getQuantities($id_farm,$date_debut,$date_fin) {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "select fullname, id_product, sum(quantity) as quantity, sum(price) as price from (
            select concat(u.lastname,' ',u.firstname) as fullname, 
            pr.id_product, 
            pr.sequence, 
            pu.quantity,
            round((pu.quantity*pd.price),2) as price
            from purchase pu
            left join user u on u.id_user = pu.fk_user
            left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
            left join product pr on pr.id_product = pd.fk_product and pr.ratio is null
            left join distribution d on d.id_distribution = pd.fk_distribution
            where pr.fk_farm = :id_farm
            and d.date BETWEEN :date_debut and :date_fin   
            group by u.id_user, pr.id_product, pu.id_purchase
            union all
            select concat(u.lastname,' ',u.firstname) as fullname, 
            pr.id_product, 
            pr.sequence, 
            pu.quantity,
            round((pu.quantity*prp.price),2) as price
            from purchase pu
            join purchase_ratio_price prp on prp.fk_purchase = pu.id_purchase
            left join user u on u.id_user = pu.fk_user
            left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
            left join product pr on pr.id_product = pd.fk_product and pr.ratio is not null
            left join distribution d on d.id_distribution = pd.fk_distribution
            where pr.fk_farm = :id_farm           
            and d.date BETWEEN :date_debut and :date_fin             
            group by u.id_user, pr.id_product, pu.id_purchase) t
            group by fullname, id_product, sequence
        order by fullname, sequence";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('date_debut'=>$date_debut->format('Y-m-d'), 'date_fin'=>$date_fin->format('Y-m-d'), 'id_farm'=>$id_farm));
        $tab = $stmt->fetchAll(\PDO::FETCH_ASSOC); 
        $out = array('by_user'=>[],'total_quantity'=>[],'total_price'=>[],'total_total_quantity'=>0,'total_total_price'=>0);
        foreach ($tab as $line) {
            if (!isset($out['by_user'][$line['fullname']])) {
                $out['by_user'][$line['fullname']] = [];
            }
            $out['by_user'][$line['fullname']][$line['id_product']] = array('quantity'=>$line['quantity'],'price'=>$line['price']);
            if (!isset($out['total_quantity'][$line['id_product']])) {
                $out['total_quantity'][$line['id_product']] = 0;
                $out['total_price'][$line['id_product']] = 0;
            }
            $out['total_quantity'][$line['id_product']] += $line['quantity'];
            $out['total_total_quantity'] += $line['quantity'];
            $out['total_price'][$line['id_product']] += $line['price'];
            $out['total_total_price'] += $line['price'];
        }
        return $out;
  }

}
