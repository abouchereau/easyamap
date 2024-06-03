<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\Payment;
use App\Entity\PaymentSplit;
use App\Entity\PaymentFreq;
use App\Util\Utils;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentRepository extends EntityRepository 
{
    protected $short_month = array('jan','fév','mar','avr','mai','juin','juil','aoû','sep','oct','nov','déc');
    
    public function getForAdherent($user, $filters, $page, $nbPerPage) {
        $qb = $this->getPaymentQb($filters);
        $qb->andWhere('u.idUser = :user')//$qb->andWhere('IDENTITY(u.idUser) = :user');
            ->setParameter('user', $user);
        $query = $qb->getQuery();
        return $this->getPaymentPaginator($query, $page, $nbPerPage);
    }
    
   public function getForReferent($user, $filters, $page, $nbPerPage) {
        $qb = $this->getPaymentQb($filters);
        if (!$user->getIsAdmin())//on met uniquement la/les ferme(s) du référent
        {
            $em = $this->getEntityManager();
            $entities = $em->getRepository('App\Entity\Farm')->findForReferent($user);
            $qb->andWhere('f.idFarm IN (:farms)');
            $qb->setParameter('farms', $entities);
        }
        $query = $qb->getQuery();
        return $this->getPaymentPaginator($query, $page, $nbPerPage);
    }
    
    private function getPaymentQb($filters) {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('p.idPayment')
            ->addSelect('c.idContract')
            ->addSelect('c.label contract')
            ->addSelect('CONCAT(COALESCE(u.firstname,\'\'),\' \',COALESCE(u.lastname,\'\')) adherent')
            ->addSelect('f.idFarm')
            ->addSelect('f.label farm')
            ->addSelect('p.description')
            ->addSelect('p.amount')
            ->addSelect('p.received')
            ->addSelect('p.receivedAt')
            ->leftJoin('App\Entity\Contract','c','WITH','p.fkContract = c.idContract')
            ->leftJoin('App\Entity\Farm','f','WITH','p.fkFarm = f.idFarm')
            ->leftJoin('App\Entity\User','u','WITH','p.fkUser = u.idUser')
            ->addOrderBy('c.periodStart', 'DESC')
            ->addOrderBy('u.lastname', 'ASC');
        if ($filters['received']!=0) {
            if ($filters['received'] == '1') {
                $qb->andWhere('p.receivedAt IS NOT NULL');
            }
            elseif ($filters['received'] == '2') {
                $qb->andWhere('p.receivedAt IS NULL');
            }
        }
        if ($filters['farm']!=0) {
            $qb->andWhere('f.idFarm=:id_farm');//$qb->andWhere('IDENTITY(f.id_farm)=:id_farm');
            $qb->setParameter('id_farm',$filters['farm']);
        }
        if ($filters['contract']!=0) {
            $qb->andWhere('c.idContract=:id_contract');//$qb->andWhere('IDENTITY(f.id_contract)=:id_contract');
            $qb->setParameter('id_contract',$filters['contract']);
        }
        if (isset($filters['adherent']) && $filters['adherent']!=0) {
            $qb->andWhere('u.idUser = :user');//$qb->andWhere('IDENTITY(u.idUser) = :user');
            $qb->setParameter('user', $filters['adherent']);
        }
        return $qb;
    }
    
    private function getPaymentPaginator($query,$page,$nbPerPage) {
        $firstResult = ($page - 1) * $nbPerPage;
        $query->setFirstResult($firstResult)->setMaxResults($nbPerPage);
        $paginator = new Paginator($query);
        if (($paginator->count() <= $firstResult) && $page != 1) {
            throw new NotFoundHttpException('La page demandée n\'existe pas.'); // page 404, sauf pour la première page
        }
        return $paginator;
    }
    
    
    public function findForUserContract($contract, $user)
    {
       $conn = $this->getEntityManager()->getConnection();
       $sql = "SELECT fk_farm, amount, description, received
         FROM payment
         WHERE fk_contract=".$contract->getIdContract()."
         AND fk_user=".$user->getIdUser();
      $r = $conn->query($sql);
      return $r->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);
    }
    
    public function compute($user, $contract, $ids_purchase_farm)
    {
      //rien à calculer : on fait rien
      if (count($ids_purchase_farm) == 0)
        return true;
      $em = $this->getEntityManager();
      
      //on récupère la/les farm concernées, ainsi que les paiments types/freq pour chacune
      //$farms = $em->getRepository('App\Entity\Purchase')->getFarmsFromPurchases($ids_purchase);//inutilisé ???
      
      //on récupère les farms des éventuels paiement non effacés (paiement reçus)
      //vérification sur les clés user, farm, contract
      $farms_received = $this->getFarmsPaymentReceived($user, $contract);
      $farms = array();  
      //on récupère la somme des prix par distribution
      $ids_purchase = array();
      foreach($ids_purchase_farm as $val) {
          $ids_purchase = array_merge($val,$ids_purchase);
      }
      $tab = $em->getRepository('App\Entity\Purchase')->getAmountByDistribution($ids_purchase);
//      print_r($tab);
      $first_distri = $em->getRepository('App\Entity\Contract')->getFirstDistributionDate($contract->getIdContract());
      $last_distri = $em->getRepository('App\Entity\Contract')->getLastDistributionDate($contract->getIdContract());
      
        //on met tout dans un tableau
        /* 
       * créer un array associatif 
       * {id_farm : {payment_types: [array],
       *             total_amount: (float),
       *             distri_amount: [array],
       *             split_payments: [[{date:amount},{date:amount}],[{date:amount},{date:amount}]]
       * }
       */

            
        $all = array();

        $discountFactor = 1;
        if ($contract->getDiscount() != null) {
            $discountFactor = 1-($contract->getDiscount()/100);
        }
        
        foreach ($tab as $line) {
            if ($farms_received === false || !in_array($line['id_farm'],$farms_received)) {
                $line['has_ratio'] = !empty($line['has_ratio']);//converti en boolean, évite le problème de valeur vide
                if (!isset($all[$line['id_farm']])) {
                    $all[$line['id_farm']] = array(
                        'payment_types' => array(), 
                        'total_amount' => 0, 
                        'split_payments' => array(), 
                        'distri_amount' => array(),
                        'chosen_payment' => array(),
                        'has_ratio_products' => false
                        );
                    $all[$line['id_farm']]['payment_types'] = $em->getRepository('App\Entity\Farm')->getPaymentTypes($line['id_farm']);
                }
                $all[$line['id_farm']]['distri_amount'][] = array(
                    'date'=>$line['date'],
                    'amount'=>$line['amount']*$discountFactor,
                    'has_ratio'=>$line['has_ratio']
                    );                
                $all[$line['id_farm']]['total_amount'] += $line['amount']*$discountFactor;
                $all[$line['id_farm']]['has_ratio_products'] = $all[$line['id_farm']]['has_ratio_products'] || $line['has_ratio'];
            }
        }
      
//        print_r($all);
//        die();
        //calcul des descriptions
        foreach ($all as $id_farm => $f) {//pour chaque farm
            try {
              $farms[$id_farm] = $em->getRepository('App\Entity\Farm')->find($id_farm);
            }
            catch(Exception $e) {
              return false;
            }
            $freqs = $em->getRepository('App\Entity\Farm')->getPaymentFreqs($id_farm);
            foreach ($freqs as $freq) {//pour chaque fréquence possible
                $split = $this->splitPayments($f['distri_amount'],$freq, $first_distri, $last_distri,$farms[$id_farm]->getEquitable());
                if (!$this->custom_in_array($split, $all[$id_farm]['split_payments'])) {//on évite les doublons (sans tenir compte de la fréquence)
                    //echo "ajout".PHP_EOL;
                    $all[$id_farm]['split_payments'][] = $split;
                } else {
                  //  echo "déjà dans l'array".PHP_EOL;
                }
                $all[$id_farm]['chosen_payment'][] = 0;
            }
            usort($all[$id_farm]['split_payments'], function ($a,$b) {return count($a) > count($b);});//classé par nombre de chèques ascendant
        }
        $payment_farm = array();
        foreach ($all as $id_farm => $each) {
            if (!isset($farms[$id_farm])) {
                try {
                  $farms[$id_farm] = $em->getRepository('App\Entity\Farm')->find($id_farm);
                }
                catch(Exception $e) {
                  return false;
                }
            }
            
            //si ratio on ajoute un paiement vide
//            if ($each['has_ratio_products']) {
//                $this->addPayment(0,$user, $farm,$contract, $each['payment_types'],$each['split_payments'],$farm->getCheckPayableTo(),$each['chosen_payment']);
//            }
            if ($each['total_amount']>0 || $each['has_ratio_products']) {
                $id_payment = $this->addPayment($each['total_amount'],$user, $farms[$id_farm],$contract, $each['payment_types'],$each['split_payments'],$farms[$id_farm]->getCheckPayableTo(),$each['chosen_payment']);
                $payment_farm[$id_farm] = $id_payment;
            }     
        }
        $this->majPurchasePayment($payment_farm,$ids_purchase_farm);
        return true;
    }
    
    private function majPurchasePayment($payment_farm,$ids_purchase_farm) {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();
        foreach($ids_purchase_farm as $farm_id => $ids_purchases) {
            if (isset($payment_farm[$farm_id])) {
                $sql = "update purchase set fk_payment=".$payment_farm[$farm_id]." where id_purchase in(".implode(',',$ids_purchases).")";
                $conn->exec($sql);
            }
        }
    }
    
    private function addPayment($amount,$user,$farm,$contract, $payment_types,$split_payments,$checkPayableTo,$chosen_payment) {
        $em = $this->getEntityManager();
        $payment = new Payment();
        $payment->setAmount(round($amount,2));
        $payment->setFkUser($user);
        $payment->setFkFarm($farm);
        $payment->setFkContract($contract);        
        $payment->setDescription(json_encode(array($payment_types,$split_payments,array($checkPayableTo),$chosen_payment),JSON_NUMERIC_CHECK));
        $payment->setReceived(0);

        try {
         $em->persist($payment);
         $em->flush();//à enlever pour la transaction ?       
        }
        catch(Exception $e) {
          return false;
        }        
        
        
        //mise à jour des purchase.fk_payment
        $sql = "";
        
        //$firstDistri = $em->getRepository('App\Entity\Contract')->getFirstDistributionDate($contract->getIdContract());
        //on ajoute par défaut un paiement global pour le contrat
        //on utilise le premier "split" possible de la liste
        // il sera splitté lorsque le référent le réceptionnera
        
        foreach ($split_payments[0] as $p_split) {//0: date, 1 : amount, 2: has_ratio, 3: monthly
            $ps = new PaymentSplit();
            $ps->setAmount($p_split[1]);
            $ps->setFkPayment($payment);
            $ps->setDate(\DateTime::createFromFormat('Y-m-d', $p_split[0]));

            try {
             $em->persist($ps);
             $em->flush();//à enlever pour la transaction ?       
            }
            catch(Exception $e) {
              return false;
            }
        }
        return $payment->getIdPayment();
    }

    private function custom_in_array($arr, $tab) {
        
        $impl = "";
        foreach ($arr as $each) {
            $impl .= $each[1].'-'.$each[2].'|';
        }
        
        foreach($tab as $subtab) {
            $same = true;
            $impl_subtab = "";
            foreach ($subtab as $subsubtab) {
                $impl_subtab .= $subsubtab[1].'-'.$subsubtab[2].'|';
            }
            
            if ($impl == $impl_subtab)
                return true;
        }
        return false;
    }
    
    private function getFarmsPaymentReceived($user, $contract) {
        //les paiements reçus sont les seuls qui n'ont pas été effacés auparavant
      $em = $this->getEntityManager();
      $conn = $em->getConnection();
        $sql = "select fk_farm
            from payment
            where fk_user=:id_user
            and fk_contract=:id_contract
			and received>0";
      $stmt = $conn->prepare($sql);
      $stmt->execute(array('id_user'=>$user->getIdUser(), 'id_contract'=>$contract->getIdContract()));
      return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    private function splitPayments($list, $freq, $first_distri, $last_distri, $equitable) {
        if ($freq == PaymentFreq::EACH_DISTRIBUTION) {
            $tab = $this->splitDistribution($list);            
        }
       else {//aggrégation des montants par fréquence de paiement
            $bounds = $this->getDateBounds($first_distri, $last_distri, $freq);           
            $tab = $this->splitMonth($list, $bounds, $freq);            
        }   
        if ($equitable) {
            $tab = $this->dispatchEquitable($tab);
        }
        $tab = array_values($tab);
        foreach($tab as $i => $item) {
            $tab[$i][1] = round($item[1],2);
        }
        return $tab;
    }
    
    // ! ne prend en compte que les distributions ayant des commandes 
    // TODO modifier pour mettre même les distributions vides ?
    /*private function splitDistributionEquitable($list) {
        $tab = array();
        //1e boucle : y a t-il des ratios  / calcul du total
        $with_ratio = false;
        $without_ratio = false;
        $total = 0;
        foreach($list as $day) {
            if ($day['has_ratio'])
                $with_ratio = true;
            else
                $without_ratio = true;
            $total += $day['amount'];
        }        
        $payment_divided = $total/count($list);
        $centimes = 0;
        $last = end($list);
        //2e boucle : on crée une entrée par distribution
        foreach ($list as $day) {
            if ($with_ratio) {
                $index = $this->generateIndex($day['date'],true);
                $tab[$index] = array($day['date'],0,1,0);
            }
            if ($without_ratio) {
                $index = $this->generateIndex($day['date'],false);
                $tab[$index] = array($day['date'],ceil($payment_divided),0,0);//arrondi à 1 € supérieur
                $centimes += ceil($payment_divided) - $payment_divided;
                if ($day == $last) {
                    $tab[$index][1] -= $centimes;//on enlève les centimes pour le dernier paiements
                    $tab[$index][1] = round($tab[$index][1],2);
                }
            }
        }        
        return $tab;
    }*/
    
    private function splitDistribution($list) {
        $tab = array();
        foreach ($list as $day) {
            $index = $day['date'].'|'.($day['has_ratio']?'1':'0');
            $tab[$index] = array($day['date'], $day['amount'], $day['has_ratio']?1:0,0);
        }        
        return $tab;
    }
    
    /*private function splitMonthEquitable($list, $bounds, $freq) {
        $tab = array();
        $nb_bounds = count($bounds);
        //1e boucle : y a t-il des ratios  / calcul du total
        $with_ratio = false;
        $without_ratio = false;
        $total = 0;
        foreach($list as $day) {
            if ($day['has_ratio'])
                $with_ratio = true;
            else
                $without_ratio = true;
            $total += $day['amount'];
        }
        //2e boucle : on créé une entrée par bounds
        for ($i = 0; $i < $nb_bounds-1 ; $i++) {
            $date = $bounds[$i]->format('Y-m-d');
            if ($without_ratio) {
                $index = $this->generateIndex($date, false);
                $tab[$index] = array($date,0,0,$freq);
            } 
            if ($with_ratio) {
                $index = $this->generateIndex($date, true);
                $tab[$index] = array($date,0,1,$freq); 
            }            
        }
        //3e boucle : on insère les paiements
        $payment_divided = $total/($nb_bounds-1);
        $centimes = 0;
        $last = end($tab);
        foreach ($tab as $index => $each) {
            if ($each[2] == 0) {
                $tab[$index][1] = ceil($payment_divided);//arrondi à 1 € supérieur
                $centimes += (ceil($payment_divided) - $payment_divided);
                if ($each == $last) {//on enlève les centimes pour le dernier paiements
                    $tab[$index][1] -= $centimes;
                    $tab[$index][1] = round($tab[$index][1],2);
                }
            }
        }
        return $tab;
    }*/
    
    private function dispatchEquitable($tab) {        
        //calcul du total
        $total = 0;
        $nb = count($tab);
        foreach($tab as $each) {
            $total += $each[1];
        }
        //on redispatch le montant
        $payment_divided = $total/$nb;
        $centimes = 0;
        $i = 0;
        foreach($tab as $index => $each) {
            $tab[$index][1] = ceil($payment_divided);//arrondi à 1 € supérieur
                $centimes += (ceil($payment_divided) - $payment_divided);
                if ($i == ($nb-1)) {//on enlève les centimes pour le dernier paiement
                    $tab[$index][1] -= $centimes;
                    $tab[$index][1] = round($tab[$index][1],2);
                }
            $i++;
        }
        return $tab;
    }
    
    private function splitMonth($list, $bounds, $freq) {
        $nb_bounds = count($bounds);
        $tab = array();
        foreach ($list as $day) {//chaque distribution
            $day_dt = \DateTime::createFromFormat('Y-m-d H:i:s',$day['date'].' 12:00:00');
            for ($i = 0; $i < $nb_bounds ; $i++) {//on place selon la tranche (1 mois / 2 mois...)
                if ($day_dt > $bounds[$i] && $day_dt <= $bounds[$i+1]) {
                    $date = $bounds[$i]->format('Y-m-d');
                    $index = $this->generateIndex($date, $day['has_ratio']);
                    if (!isset($tab[$index])) {
                        $tab[$index] = array($date,0,($day['has_ratio']?1:0),$freq);//0: date, 1 : amount, 2: has_ratio, 3: monthly
                    }
                    $tab[$index][1] += $day['amount'];
                }
            }
        }
        return $tab;
    }
    
    private function generateIndex($date, $has_ratio) {
        return $date.'|'.($has_ratio?'1':'0');
    }
    
    private function getDateBounds($first_distri, $last_distri, $freq) {
        $tab = array();
        $month_begin = substr($first_distri, 0, 8).'01 01:00:00';
        $bound = \DateTime::createFromFormat('Y-m-d H:i:s', $month_begin);        
        $end = \DateTime::createFromFormat('Y-m-d', $last_distri);
        
        $nb_month = PaymentFreq::getNbMonth($freq);        
        $interval = new \DateInterval('P'.$nb_month.'M');
        
        while ($bound < $end) {
            $tab[] = clone $bound;
            $bound->add($interval);
        }
        $tab[] = \DateTime::createFromFormat('Y-m-d', '2030-12-12');
        return $tab;        
    }
    
    
    public function emptyPayments($user, $contract,$referent=null)
    {
      $em = $this->getEntityManager();
      $conn = $em->getConnection();
      if ($referent == null || $referent->getIsAdmin()) {
        $sql = "delete p, ps
              from payment p
              inner join payment_split ps on ps.fk_payment = p.id_payment
              where fk_user=:id_user and fk_contract=:id_contract
              and p.received=0";     //ne pas effacer les paiements qui sont déjà reçus 
        $stmt = $conn->prepare($sql);
        $v = $stmt->execute(array('id_user'=>$user->getIdUser(), 'id_contract'=>$contract->getIdContract()));
      }
      else {
        $sql = "delete p, ps
              from payment p
              inner join payment_split ps on ps.fk_payment = p.id_payment
              left join referent r on r.fk_farm = p.fk_farm              
              where p.fk_user=:id_user and fk_contract=:id_contract
              and r.fk_user=:id_referent
              and p.received=0";     
        $stmt = $conn->prepare($sql);
        $v = $stmt->execute(array(
            'id_user'=>$user->getIdUser(), 
            'id_contract'=>$contract->getIdContract(),
            'id_referent' => $referent->getIdUser()));  
      }
      return $v;
    }
    
    public function findForUser($user)
    {
        return $this
         ->createQueryBuilder('p')
         ->where('p.fkUser = :user')
         ->addOrderBy('p.idPayment','DESC')
         ->setParameter('user', $user)
         ->getQuery()
         ->getResult();
    }
    
    public function getNbNotReceived($user)
    {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT COUNT(id_payment) AS nb 
          FROM payment 
          WHERE fk_user=:id_user
          AND received_at is null";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['id_user' => $user->getIdUser()]);
      return $stmt->fetch(\PDO::FETCH_COLUMN);
    }
    
    public function stats($year, $id_user=null, $id_farm=null) 
    {
        $mois = array('','Jan','Fev','Mar','Avr','Mai','Juin','Juil','Aou','Sep','Oct','Nov','Dec');
        $conn = $this->getEntityManager()->getConnection();
        $params = [];
        $sql = "select month(date), round(sum(price),2) as somme 
            from (
            select fk_purchase, date, p.quantity*price as price
            from view_purchase_ratio_price prp
            left join purchase p on p.id_purchase = prp.fk_purchase
            where date between '".$year."-01-01' AND '".$year."-12-31'";
            if ($id_user != null) {
                $sql .= " and prp.fk_user=:id_user";
                $params['id_user'] = $id_user;
            }
            if ($id_farm != null) {
                $sql .= " and prp.fk_farm=:id_farm";
                $params['id_farm'] = $id_farm;
            }
            $sql .= " group by fk_purchase, date
            union all
            select pu.id_purchase, di.date, pu.quantity*pd.price as price
            from purchase pu
            left join product_distribution pd on pu.fk_product_distribution = pd.id_product_distribution
            left join distribution di on pd.fk_distribution = di.id_distribution
            left join product pr on pd.fk_product = pr.id_product
            where di.date between '".$year."-01-01' AND '".$year."-12-31'
            and pr.ratio is null";
            if ($id_user != null)
                $sql .= " and pu.fk_user=".$id_user;
            if ($id_farm != null) 
                $sql .= " and pr.fk_farm=".$id_farm;
            $sql .= " group by pu.id_purchase, di.date
            union all
            select null as id_purchase, p.received_at as date, p.received as price
            from payment p
            where 1=1";
            if ($id_user != null)
                $sql .= " and p.fk_user=".$id_user;
        if ($id_farm != null)
            $sql .= " and p.fk_farm=".$id_farm;
        $sql .= " and p.received_at between '".$year."-01-01' AND '".$year."-12-31'
            and not exists (select fk_payment from purchase pu where pu.fk_payment = p.id_payment)
            ) pre_somme
            group by month(date)";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $tab = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        $out = array();
        $total = 0;
        for ($i=1; $i<=12; $i++) 
        {
            $montant = 0;
            if (isset($tab[$i]))
                $montant = $tab[$i];  
            $total += $montant;
            $out[] = array('label' => $mois[$i], 'y' => (float)$montant, 'indexLabel'=>  number_format($montant, 2, ',',' ')." €");            
        }
        return array('graph' => $out, 'total' => $total);
    }
    
   /* public function majStat($id_payment) {
        $conn = $this->getEntityManager()->getConnection();
        try {
            $sql = "delete from purchase_ratio_price
                    where fk_purchase in (
            select pu.id_purchase
            from purchase pu
            where pu.fk_payment=:id_payment)";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['id_payment' => $id_payment]);                 
            
            
            $sql = "insert into purchase_ratio_price(fk_purchase, date, fk_user, fk_farm, price)
                select pu.id_purchase, d.date, pu.fk_user, pay.fk_farm, (pay.amount-ifnull(j1.somme,0))/j2.nb_product_prix_poids as prix_estime
                from purchase pu
                left join payment pay on pu.fk_payment = pay.id_payment
                join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
                join distribution d on d.id_distribution = pd.fk_distribution
                join product pr on pr.id_product = pd.fk_product
                left join (
                select id_payment, fk_contract, fk_user, amount, round(sum(price),2) as somme from (
                        select pay.id_payment, pay.fk_contract, pu.fk_user, pay.amount, pu.quantity*pd.price as price	
                        from purchase pu
                        left join payment pay on pu.fk_payment = pay.id_payment
                        left join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
                        join product pr on pr.id_product = pd.fk_product
                    where pr.ratio is null
                    group by pay.id_payment
                ) tt group by id_payment
                ) j1 on j1.id_payment = pay.id_payment and j1.fk_contract = pu.fk_contract
                left join (
                select pay.id_payment, pay.fk_contract, pay.fk_user, pay.amount, sum(pu.quantity) as nb_product_prix_poids
                        from payment pay
                        join purchase pu on pu.fk_payment = pay.id_payment
                        join product_distribution pd on pd.id_product_distribution = pu.fk_product_distribution
                        join product pr on pr.id_product = pd.fk_product 
                        where pr.ratio is not null
                        group by pay.id_payment
                ) j2 on j2.id_payment = pay.id_payment and j2.fk_contract = pu.fk_contract
                where pr.ratio is not null
                and pay.fk_farm is not null
                and pu.fk_payment=:id_payment";
                $stmt = $conn->prepare($sql);
                $stmt->execute(['id_payment' => $id_payment]);    
        }
        catch(\Exception $e) {
            return false;
        }
        return true;
    }*/
    
    public function getAllYears() 
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select distinct(year(period_start)) from contract";
        $r = $conn->query($sql);
        return $r->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    public function hasOverage() 
    { 
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select count(id_product_distribution) as nb from view_overage";
        $r = $conn->query($sql);
        return $r->fetch(\PDO::FETCH_COLUMN)>0;
    }
    
    public function getOverages($product_distributions)
    {
        $pds = array();
        foreach ($product_distributions as $pd) {
            $pds[] = $pd['id_product_distribution'];
        }

        $conn = $this->getEntityManager()->getConnection();
        $sql = "select p.label, p.unit, d.date, vo.excedent
                from view_overage vo
                left join product p on vo.fk_product = p.id_product
                left join product_distribution pd on vo.id_product_distribution = pd.id_product_distribution
                left join distribution d on d.id_distribution = pd.fk_distribution
                where vo.id_product_distribution in(".implode(',',$pds).")";//TODO requete preparee
        $r = $conn->query($sql);
        return $r->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function history($user, $farm, $year) {      
        $sql = "select 
            p.id_payment, 
            min(ps.date) as date,
            c.label as contractLabel,
            p.description,
            p.amount,
            p.received
            from payment p
            left join contract c on c.id_contract = p.fk_contract
            left join payment_split ps on ps.fk_payment = p.id_payment
            where p.fk_user=:id_user
            and p.fk_farm=:id_farm
            and year(ps.date)=:year
            group by p.id_payment
            order by min(ps.date) ASC";        
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('id_user'=> $user->getIdUser(),'id_farm'=> $farm->getIdFarm(), 'year'=>$year));
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function getDistinctYears() {
        $sql = "select 
            distinct(year(case when p.received_at is null then c.period_start else p.received_at end)) as year
            from payment p
            left join contract c on c.id_contract = p.fk_contract
            order by year(case when p.received_at is null then c.period_start else p.received_at end) ASC";
        $conn = $this->getEntityManager()->getConnection();
        $r = $conn->query($sql);
        return $r->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    public function getForContract($id_contract, $id_farm = null) {
        $conn = $this->getEntityManager()->getConnection();
        $params = ['id_contract' => $id_contract];
        $sql = "SELECT fk_user, SUM(amount) AS amount, SUM(received) AS received
                 FROM payment
                 WHERE fk_contract=:id_contract";
        if ($id_farm != null) {         
            $sql .= " AND fk_farm=:id_farm";
            $params['id_farm'] = $id_farm;
        }
        $sql .= " GROUP BY fk_user";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);
    }
    
    public function checkReceived($amount,$split) {               
        $sum = round(array_sum(array_map(function($v) {return 1.0*$v;},$split)),2);
        return (1.0*$amount) === $sum;
    }
    
    public function manageReceived($id_payment,$amount,$split_index,$split) {
        $em = $this->getEntityManager();
        //on met à jour le description du paiement et le montant
        $payment = $this->find($id_payment);
        if ($split_index > -1) {
            $description = json_decode($payment->getDescription());

            $payments = $description[1];
            $chosen = $description[3];

            //dans la description on met à jour le paeiment choisi
            foreach ($chosen as $i => $val) {
                $description[3][$i] = ($i==$split_index?1:0);
            }
            //on met à jour les montants des chèques
            foreach ($split as $i => $tab) {
                $description[1][$split_index][$i][1] = Utils::numerize($split[$i]);
            }
            $payment->setDescription(json_encode($description));
        }
        $payment->setReceived($amount);
        $payment->setreceivedAt(new \DateTime());
        
        try {
             $em->persist($payment);
             $em->flush();   
        } catch(Exception $e) {
              return $e->getMessage();
        }  
        
        if ($split_index > -1) {
            //on efface l'ancien split
            $v = $em->getRepository('App\Entity\PaymentSplit')->removeFromPayment($payment);
            if ($v !== true) {
                return $v;
            }

            //on enregistre le nouveau split
            $v = $em->getRepository('App\Entity\PaymentSplit')->addMany($payments[$split_index], $split, $payment);
            if ($v !== true) {
                return $v;
            }
            return $description;
        }
        else {
            return '[]';
        }
    }
}