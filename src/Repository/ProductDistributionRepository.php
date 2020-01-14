<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class ProductDistributionRepository extends EntityRepository
{
  //si user = null : admin
  public function findAllWhereDistributionIn($ids_distribution, $user = null)
  {
    $conn = $this->getEntityManager()->getConnection();
    $sql = "SELECT 
      CONCAT(pd.fk_product, '-', pd.fk_distribution) AS id,
      pd.id_product_distribution,
      COUNT(p.id_purchase) AS nb_purchase,
      pd.price,
      pd.max_quantity,
      pd.max_per_user
      FROM product_distribution pd
      LEFT JOIN purchase p ON p.fk_product_distribution = pd.id_product_distribution";
    
    if ($user != null)
    {
      $sql .= " LEFT JOIN product pr ON pr.id_product = pd.fk_product
        LEFT JOIN farm f ON f.id_farm = pr.fk_farm
        LEFT JOIN referent r ON r.fk_farm = f.id_farm";
    }
    
    if (count($ids_distribution) > 0)
      $sql .= " WHERE pd.fk_distribution IN(".implode(',',$ids_distribution).")";
    else
      $sql .= " WHERE 1=0";
    
    if ($user != null)
      $sql .= " AND r.fk_user=".$user->getIdUser();    
    $sql .= " GROUP BY pd.id_product_distribution";//TODO requete preparee
    $r = $conn->query($sql);
    return $r->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
  }
  
  public function save($existing, $new_ones)
  {
    $conn = $this->getEntityManager()->getConnection();

    $nb_suppr = 0;  
    $nb_insert = 0;
    
    //on efface ceux qui étaient en base et ont disparu
    foreach ($existing as $id_product_distribution => $checked)
    {
      if ($checked == '0')
      {
        $sql = "DELETE FROM product_distribution WHERE id_product_distribution=".$id_product_distribution;//TODO requete preparee
        $conn->exec($sql);
        $nb_suppr++;
      }
    }
      
    //on insère ceux qui n'étaient pas encore insérés
    foreach ($new_ones as $id => $checked)
    {
      if ($checked == '1')
      {
        $tmp = explode('-',$id);
        $product = $tmp[0];
        $distribution = $tmp[1];
        $sql = "INSERT INTO product_distribution(fk_product, fk_distribution, price)
              SELECT ".$product.", ".$distribution.", base_price
              FROM product WHERE id_product=".$product;        
        $conn->exec($sql);
        $nb_insert++;
      }
    }
    //on retourne le nb d'insertions suppression
    return array($nb_insert, $nb_suppr);
  }
  
  public function retrieveFromContract($id_contract)
  {
    $conn = $this->getEntityManager()->getConnection();
    $sql = "SELECT 
      CONCAT(pd.fk_product, '-', pd.fk_distribution) AS id,      
      pd.id_product_distribution,    
      pd.price,
      pd.max_per_user
      FROM contract c
      LEFT JOIN distribution d ON d.date BETWEEN c.period_start AND c.period_end
      LEFT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
      INNER JOIN contract_product cp ON cp.fk_product = pd.fk_product AND cp.fk_contract = c.id_contract
      WHERE c.id_contract = ".$id_contract."
      GROUP BY pd.id_product_distribution";//TODO requete preparee
    $r = $conn->query($sql);
    return $r->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
  }
  
  public function getRemaining($id_contract)
  {
    $conn = $this->getEntityManager()->getConnection();
    $sql = "select
                CONCAT(pd.fk_product, '-', pd.fk_distribution) AS id,  
                pd.max_quantity, 
                SUM(p.quantity),
                IFNULL(pd.max_quantity-SUM(p.quantity),pd.max_quantity) as remaining
            FROM product_distribution pd 
            left join purchase p on p.fk_product_distribution = pd.id_product_distribution
            where p.fk_contract=".$id_contract."
            and pd.max_quantity is not null
            group by pd.id_product_distribution";//TODO requete preparee
    /*select
                CONCAT(pd.fk_product, '-', pd.fk_distribution) AS id,  
                pd.max_quantity, 
                IFNULL(pd.max_quantity-SUM(p.quantity),pd.max_quantity) as remaining
            FROM contract c
            LEFT JOIN distribution d ON d.date BETWEEN c.period_start AND c.period_end
            LEFT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
            left join purchase p on p.fk_product_distribution = pd.id_product_distribution
            INNER JOIN contract_product cp ON cp.fk_product = pd.fk_product AND cp.fk_contract = c.id_contract
            where c.id_contract=".$id_contract."
            and pd.max_quantity is not null
            group by pd.id_product_distribution";*/
    $r = $conn->query($sql);
    return $r->fetchAll(\PDO::FETCH_UNIQUE|\PDO::FETCH_ASSOC);
  }
  
  public function getLivraisonsInMonth($mois, $annee) {
      $sql = "SELECT * FROM (
SELECT 
CONCAT(d.date,'_', p.fk_farm) AS id, 1
            FROM product_distribution pd
            left join distribution d on pd.fk_distribution = d.id_distribution
            left join product p on p.id_product = pd.fk_product
            WHERE month(d.DATE)=1 
				AND year(d.DATE)=2020 
				AND p.is_active=1
				AND pd.fk_distribution_shift IS NULL
            group by d.date, p.fk_farm
UNION
SELECT
CONCAT(d2.date,'_', p.fk_farm) AS id, 1
            FROM product_distribution pd
            left join distribution d on pd.fk_distribution = d.id_distribution
            left join distribution d2 on pd.fk_distribution_shift = d2.id_distribution
            left join product p on p.id_product = pd.fk_product
            WHERE month(d.DATE)=1 
				AND year(d.DATE)=2020 
				AND p.is_active=1
				AND pd.fk_distribution_shift IS NOT NULL
            group by d.date, p.fk_farm
) v
order BY v.id";
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('mois' => $mois, 'annee' => $annee));
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR); 
  }

    
  public function getFarmForDistribution($id_distribution) {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "select distinct f.product_type, f.label, f.sequence
            from product_distribution pd
            left join product p on pd.fk_product = p.id_product
            left join farm f on p.fk_farm = f.id_farm
            where pd.fk_distribution=:id_distribution
            order by f.sequence";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id_distribution' => $id_distribution]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC); 
  }
}