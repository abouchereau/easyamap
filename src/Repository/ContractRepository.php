<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\User;

class ContractRepository extends EntityRepository 
{
    public function findAllOrderByIdDesc($user)
    {
        //test branch
        $params = [];
        $conn = $this->getEntityManager()->getConnection();        
        $sql = "select 
                c.id_contract,
                c.label,
                c.is_active,
                c.is_visible,
                c.fk_user,
                u.lastname as creator,
                c.fill_date_end,
                min(d.date) as first_distribution,
                max(d.date) as last_distribution,
                count(distinct(d.id_distribution)) as nb_distribution,
                count(distinct(cp.fk_product)) as nb_product
                from contract c 
                left join distribution d on d.date between c.period_start and c.period_end 
                left join contract_product cp on cp.fk_contract = c.id_contract ";
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $sql .= " left join user u on u.id_user = c.fk_user";
        }
        elseif ($user->hasRole(User::ROLE_REFERENT)) {
            $sql .= " left join product p on p.id_product = cp.fk_product
                left join farm f on f.id_farm = p.fk_farm
                left join referent r on r.fk_farm = f.id_farm
                left join user u on u.id_user = c.fk_user
                where r.fk_user=:id_user";
            $params['id_user'] = $user->getIdUser();
        } elseif ($user->hasRole(User::ROLE_FARMER)) {
            $sql .= " left join product p on p.id_product = cp.fk_product
                left join farm f on f.id_farm = p.fk_farm                
                left join user u on u.id_user = c.fk_user
                where f.fk_user=:id_user";
            $params['id_user'] = $user->getIdUser();
        }
        $sql .= " group by c.id_contract
                order by c.id_contract desc";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function findAllOrderByPeriodStart()
    {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "select c.id_contract as idContract, 
            c.label, 
            c.is_active as isActive, 
            count(distinct(d.id_distribution)) as nbDistribution,
            min(d.date) as firstDistribution,
            c.fill_date_end as fillDateEnd,
            case when c.fill_date_end <= date_sub(now(),interval 1 hour) then 1 else 0 end as isArchive
            from contract c
            left join distribution d on d.date between c.period_start and c.period_end
            where c.is_visible=1
            group by c.id_contract
            order by c.period_start desc";
      $r = $conn->query($sql);
      return $r->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function findAllOrderByIdDescDoctrine($user) {
        $qb = $this->createQueryBuilder('c');                
        if (!$user->getIsAdmin()) {
            $qb->leftJoin('c.products', 'p')
                ->leftJoin('p.fkFarm', 'f')
                ->leftJoin('f.referents','u')
                ->where('u.idUser = :user')
                ->setParameter('user', $user);
        }
        return $qb->addOrderBy('c.idContract', 'DESC')
                ->getQuery()
                ->getResult();
    }
    
    public function getActiveContracts()
    {
      return array_map('current',$this->createQueryBuilder('c')
      ->select('c.label')
      ->where('c.isActive = 1')
      ->orderBy('c.idContract', 'DESC')
      ->getQuery()
      ->getArrayResult());
    }
    
    public function canBeDeleted($id_contract)
    {
      //on regarde si des acchats ont eu lieu sur le contrat
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT COUNT(fk_contract) AS nb FROM view_deletable_contract WHERE fk_contract=:id_contract";
      $query = $conn->executeQuery($sql, array('id_contract' => $id_contract));
      return $query->fetchColumn()==1;
    }
    
   /* public function getOverlappingContracts()
    {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT c1.id_contract, c2.id_contract
          FROM contract c1, contract c2
          WHERE c1.id_contract != c2.id_contract 
          AND (
          (c1.period_start BETWEEN c2.period_start AND c2.period_end)
          OR 
          (c1.period_end BETWEEN c2.period_start AND c2.period_end)
          )";
      $r = $conn->query($sql);
      return $r->fetchAll(\PDO::FETCH_COLUMN);
    }*/
    
    public function getOverlappingContractsWithSameProducts($id_contract = null)
    {
      $params = [];
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT contrat, distribution, produit
              FROM view_contract_conflict";
      if ($id_contract != null) {
        $sql .= " WHERE id_contract = :id_contract";
        $params['id_contract'] = $id_contract;
      }
      $stmt = $conn->prepare($sql);
      $stmt->execute($params);
      return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function nbPurchaser()
    {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT id_contract, nb_purchaser
              FROM view_contract_nb_purchaser";
      $r = $conn->query($sql);
      return $r->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
    
    public function getPurchasers($id_contrat)
    {
      $conn = $this->getEntityManager()->getConnection();
      $sql = 'SELECT 
              CASE WHEN vcp.fk_user IS NULL THEN "no" ELSE "yes" END AS answered,
              u.firstname, u.lastname, u.email              
              FROM user u
              LEFT JOIN view_contract_purchaser vcp ON vcp.fk_user = u.id_user AND vcp.id_contract = :id_contract
              WHERE u.is_active=1
              ORDER BY u.lastname ASC';
      $stmt = $conn->prepare($sql);
      $stmt->execute(['id_contract'=>$id_contrat]);
      return $stmt->fetchAll(\PDO::FETCH_GROUP);
    }
    
    public function getAvailabilities($id_contract)
    {
      $conn = $this->getEntityManager()->getConnection();
      $sql = 'SELECT 
              pd.fk_product,
              d.date,
              IFNULL(p.quantity,0) AS nb_purchase
              FROM contract c
              LEFT JOIN distribution d ON d.date BETWEEN c.period_start AND c.period_end
              LEFT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
              INNER JOIN contract_product cp ON cp.fk_product = pd.fk_product AND cp.fk_contract = c.id_contract
              LEFT JOIN purchase p ON p.fk_product_distribution = pd.id_product_distribution
              WHERE c.id_contract = :id_contract';
      
      $stmt = $conn->prepare($sql);
      $stmt->execute([':id_contract' => $id_contract]);
      $tab = $stmt->fetchAll(\PDO::FETCH_GROUP);
      $new = array();
      foreach ($tab as $fk_product => $dates)
      {
        if (!isset($new[$fk_product]))
          $new[$fk_product] = array();
        foreach ($dates as $date)
        {
          $new[$fk_product][$date['date']] = (int)$date['nb_purchase'];
        }
      }
      return $new;
    }
    
    public function getDistributions($id_contract)
    {
      $conn = $this->getEntityManager()->getConnection();
      $sql = 'SELECT d.id_distribution, d.date
              FROM contract c
              LEFT JOIN distribution d ON (d.date BETWEEN c.period_start AND c.period_end)
              WHERE c.id_contract = :id_contract
              ORDER BY d.date';
      $stmt = $conn->prepare($sql);
      $stmt->execute(['id_contract' => $id_contract]);
      return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
    
    public function getCommandesExistantes($id_contract, $id_user) {
       $conn = $this->getEntityManager()->getConnection();
       $sql = 'SELECT d.id_distribution, case when sum(pu.id_purchase) is null then 0 else 1 end commande_existante
              FROM contract c
              LEFT JOIN distribution d ON (d.date BETWEEN c.period_start AND c.period_end)
              left join product_distribution pd on d.id_distribution = pd.fk_distribution
              left join purchase pu on (pu.fk_product_distribution = pd.id_product_distribution and pu.fk_user=:id_user)
              WHERE c.id_contract = :id_contract
              group by d.id_distribution, d.date
              ORDER BY d.date';
       $stmt = $conn->prepare($sql);
       $stmt->execute(array('id_contract'=>$id_contract,'id_user'=>$id_user));
       return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
    
    public function getFirstDistributionDate($id_contract) {
      $conn = $this->getEntityManager()->getConnection();
      $sql = 'SELECT d.date
              FROM contract c
              LEFT JOIN distribution d ON (d.date BETWEEN c.period_start AND c.period_end)
              WHERE c.id_contract=:id_contract
              ORDER BY d.date
              LIMIT 1';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array('id_contract'=>$id_contract));
      return $stmt->fetch(\PDO::FETCH_COLUMN);
    }
    
    public function getLastDistributionDate($id_contract) {
      $conn = $this->getEntityManager()->getConnection();
      $sql = 'SELECT d.date
              FROM contract c
              LEFT JOIN distribution d ON (d.date BETWEEN c.period_start AND c.period_end)
              WHERE c.id_contract=:id_contract
              ORDER BY d.date DESC
              LIMIT 1';
      $stmt = $conn->prepare($sql);
      $stmt->execute(array('id_contract'=>$id_contract));
      return $stmt->fetch(\PDO::FETCH_COLUMN);
    }
    
    public function getProductsForCalendar($id_contract)
    {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT f.label, p.id_product AS id, CONCAT(ifnull(p.label,''), ' ', ifnull(p.unit,'')) AS nom
              FROM contract c
              LEFT JOIN contract_product cp ON cp.fk_contract = c.id_contract
              LEFT JOIN product p ON p.id_product = cp.fk_product
              LEFT JOIN farm f ON f.id_farm = p.fk_farm
              WHERE c.id_contract = :id_contract
              ORDER BY f.label, p.label";
      $stmt = $conn->prepare($sql);
      $stmt->execute(array('id_contract'=>$id_contract));
      return $stmt->fetchAll(\PDO::FETCH_GROUP);
    }
    
    public function getProductsOrderByFarm($id_contract)
    {
      
    }
      
    public function getReport($id_contract, $id_farm = null) {
      $conn = $this->getEntityManager()->getConnection();
      $params = [];
      
      $sql = "SELECT 
                CONCAT(ifnull(u.lastname,''), ' ',ifnull(u.firstname,'')) AS name,
                u.id_user,
                d.date,
                pr.label,
                pr.unit,
                pr.ratio,
                p.quantity,
                pd.price
                from purchase p
                LEFT JOIN user u ON u.id_user = p.fk_user
                LEFT JOIN product_distribution pd ON pd.id_product_distribution = p.fk_product_distribution
                LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution
                LEFT JOIN product pr ON pr.id_product = pd.fk_product
                LEFT JOIN farm f ON pr.fk_farm = f.id_farm
                WHERE p.fk_contract=:id_contract";
        if ($id_farm != null) {
            $sql .= " AND pr.fk_farm=:id_farm";            
            $params['id_farm'] = $id_farm;
        }
        $sql .= " GROUP BY p.id_purchase
                ORDER BY u.lastname, d.date, f.sequence, pr.sequence";
        $params['id_contract'] = $id_contract;
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);      
      return $stmt->fetchAll(\PDO::FETCH_GROUP);
    }
    
    public function getShipping($id_contract, $id_farm = null) {
        $conn = $this->getEntityManager()->getConnection();
        $params = [];
        $sql = "SELECT 
                pr.id_product,
                pr.label,
                d.date,
                pr.unit,
                pr.ratio,
                pr.base_price,
                SUM(p.quantity) as quantity,
                pd.price
                FROM contract c               
                LEFT JOIN purchase p ON p.fk_contract = c.id_contract
                LEFT JOIN product_distribution pd ON pd.id_product_distribution = p.fk_product_distribution
                LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution
                LEFT JOIN product pr ON pr.id_product = pd.fk_product
                LEFT JOIN farm f ON pr.fk_farm = f.id_farm
                WHERE c.id_contract=:id_contract";
        $params['id_contract'] = $id_contract;
      if ($id_farm != null) {
          $sql .= " AND pr.fk_farm=:id_farm";
          $params['id_farm'] = $id_farm;
      }
        $sql .= " GROUP BY pr.id_product, d.id_distribution, pr.label, d.date, pr.unit, pr.ratio, pr.base_price, pd.price 
                ORDER BY f.sequence, pr.sequence, d.date";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
      $tab = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      $new_tab = array();
      foreach ($tab as $infos) {
        if (!isset($new_tab[$infos['id_product']])) {  
            $new_tab[$infos['id_product']] = array(
                'label' => $infos['label'],
                'unit' => $infos['unit'],
                'ratio' => $infos['ratio'],
                'base_price' => $infos['base_price'],
                'distris' => array());
         }
         $new_tab[$infos['id_product']]['distris'][$infos['date']] = array('quantity' => $infos['quantity'], 'price' => $infos['price']);
      }
      return $new_tab;
    }
    
    public function getVentilation($id_contract, $id_farm = null) {
        $conn = $this->getEntityManager()->getConnection();
        $params = [];
        $sql = "SELECT 
                pr.id_product,
                pr.label,
                pr.base_price,
                pr.unit,
                d.date,
                SUM(p.quantity) AS quantity,
                u.lastname
                FROM contract c                
                LEFT JOIN purchase p ON p.fk_contract = c.id_contract                
                LEFT JOIN product_distribution pd ON pd.id_product_distribution = p.fk_product_distribution
                LEFT JOIN distribution d ON d.id_distribution = pd.fk_distribution
                LEFT JOIN product pr ON pr.id_product = pd.fk_product
                LEFT JOIN farm f ON pr.fk_farm = f.id_farm
                LEFT JOIN user u ON u.id_user = p.fk_user
                WHERE c.id_contract=:id_contract";
        $params['id_contract'] = $id_contract;
        if ($id_farm != null) {
          $sql .= " AND pr.fk_farm=:id_farm";
          $params['id_farm'] = $id_farm;
        }
        $sql .= " GROUP BY u.lastname, pr.id_product,d.date ORDER BY f.sequence, pr.sequence, d.date, u.lastname";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $tab = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $new_tab = array();
        foreach ($tab as $infos) {
          if (!isset($new_tab[$infos['id_product']])) {  
              $new_tab[$infos['id_product']] = array(
                  'label' => $infos['label'],
                  'unit' => $infos['unit'],
                  'base_price' => $infos['base_price'],
                  'distris' => array());
           }
           if (!isset($new_tab[$infos['id_product']]['distris'][$infos['date']])) {  
               $new_tab[$infos['id_product']]['distris'][$infos['date']] = array();
           }
           if (!empty($infos['lastname'])) {
            $new_tab[$infos['id_product']]['distris'][$infos['date']][$infos['lastname']] = $infos['quantity'];
           }
          
        }
        return $new_tab;
        
    }
    
    //paiments par mois par produit pour un contrat
    public function getShippingPayment($id_contract, $id_farm = null) {
        $conn = $this->getEntityManager()->getConnection();
        $params = ['id_contract' => $id_contract];
        $sql = "SELECT 
                pr.id_product,
                DATE_FORMAT(d.date,'%Y-%m') as date,                
                round(SUM(pd.price * ifnull(p.quantity,0)),2) as price
                FROM contract c
                LEFT JOIN distribution d ON d.date BETWEEN c.period_start AND c.period_end
                RIGHT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
                INNER JOIN contract_product cp ON cp.fk_product = pd.fk_product AND cp.fk_contract = c.id_contract
                LEFT JOIN purchase p ON p.fk_product_distribution = pd.id_product_distribution
                LEFT JOIN product pr ON pr.id_product = cp.fk_product
                WHERE c.id_contract=:id_contract";
              //ajout 22/10/2017
        $sql .= " AND p.fk_contract=:id_contract";
        if ($id_farm != null) {
          $sql .= " AND pr.fk_farm=:id_farm";
          $params['id_farm'] = $id_farm;
        }
        $sql .= " group by DATE_FORMAT(d.date,'%Y-%m'), pr.id_product";
            
        $sql .= " union
                SELECT 
                'all' as id_product,
                DATE_FORMAT(d.date,'%Y-%m') as date,                
                round(SUM(pd.price * ifnull(p.quantity,0)),2) as price
                FROM contract c
                LEFT JOIN distribution d ON d.date BETWEEN c.period_start AND c.period_end
                RIGHT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
                INNER JOIN contract_product cp ON cp.fk_product = pd.fk_product AND cp.fk_contract = c.id_contract
                LEFT JOIN purchase p ON p.fk_product_distribution = pd.id_product_distribution
                LEFT JOIN product pr ON pr.id_product = cp.fk_product
                WHERE pr.ratio is null
                and c.id_contract=:id_contract";
                $sql .= " AND p.fk_contract=:id_contract";
            if ($id_farm != null) {
              $sql .= " AND pr.fk_farm=:id_farm";
            }
            $sql .= "
                group by DATE_FORMAT(d.date,'%Y-%m'), month(d.date)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $tab = $stmt->fetchAll(\PDO::FETCH_GROUP);
        $new_tab = array();
        foreach ($tab as $id_product => $arr) {
            if (!isset($new_tab[$id_product])) {
                $new_tab[$id_product] = array();
            }
            foreach($arr as $item) {
                $new_tab[$id_product][$item['date']] = $item['price'];
            }
        }
        return $new_tab;
    }
    
    public function getPaymentByMonth($id_contract, $id_farm = null) {
        $conn = $this->getEntityManager()->getConnection();
        $params = array('id_contract' => $id_contract);
        $sql = "select 
                f.label as producteur,
                concat(ifnull(u.lastname,''), ' ',ifnull(u.firstname,'')) as adherent,
                date_format(ps.date, '%Y-%m') as mois,
                round(ps.amount,2) as paiement
                from payment_split ps
                left join payment p on p.id_payment = ps.fk_payment
                left join user u on u.id_user = p.fk_user
                left join farm f on f.id_farm = p.fk_farm
                where p.fk_contract = :id_contract";
        if ($id_farm != null) {
            $sql .= " and p.fk_farm = :id_farm";
            $params['id_farm'] = $id_farm;
        }
        $sql .= " order by f.sequence, u.lastname, ps.date";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $tmp = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $tab = array();
        foreach ($tmp as $t) {
          if (!isset($tab[$t['producteur']])) {
              $tab[$t['producteur']] = array();
          }
          if (!isset($tab[$t['producteur']][$t['adherent']])) {
              $tab[$t['producteur']][$t['adherent']] = array();
          }
          if (!isset($tab[$t['producteur']][$t['adherent']][$t['mois']])) {
              $tab[$t['producteur']][$t['adherent']][$t['mois']] = array();
          }
          $tab[$t['producteur']][$t['adherent']][$t['mois']][] = 1.0*$t['paiement'];
        }
        return $tab;
    }
    
    public function getProductPurchased($id_contract) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select pd.fk_product,1
                from purchase p
                left join product_distribution pd on p.fk_product_distribution = pd.id_product_distribution
                where p.fk_contract = :id_contract
                group by pd.fk_product
                having IFNULL(sum(p.quantity),0) > 0";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id_contract'=>$id_contract]);
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
    
    public function getNbProductAvailable($id_contract) {
        $conn = $this->getEntityManager()->getConnection();        
        $sql = "select count(*) as nb 
            from view_contract_distribution_product
            where fk_contract=:id_contract";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('id_contract'=>$id_contract));
        return $stmt->fetch(\PDO::FETCH_COLUMN);
    }
    
    public function getFilledContracts($user) {
        $conn = $this->getEntityManager()->getConnection();        
        $sql = "select id_contract, '1' 
            from view_contract_purchaser
            where fk_user=:id_user";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('id_user'=>$user->getIdUser()));
        return $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
    
    public function hasEquitableAndRatio($contract) {
        $conn = $this->getEntityManager()->getConnection();      
        $sql = "SELECT count(p.id_product)
                from contract c
                left join contract_product cp on cp.fk_contract = c.id_contract
                left join product p on p.id_product = cp.fk_product
                left join farm f on f.id_farm = p.fk_farm
                where p.ratio is not null 
                and f.equitable=1
                and c.id_contract=:id_contract";
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('id_contract'=>$contract->getIdContract()));
        return $stmt->fetch(\PDO::FETCH_COLUMN)>0;
        
    }
}