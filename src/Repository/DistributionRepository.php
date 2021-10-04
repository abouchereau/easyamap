<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use \DateTime;
use App\Entity\Distribution;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DistributionRepository extends EntityRepository 
{

    protected $all = null;

  public function toggle($date)
  {    
    $id_distribution = $this->retrieveFromDate($date);
    if ($id_distribution === false)
    {
      $this->addDistri($date);
      return true;
    }
    else
    {
      $this->removeDistri($id_distribution);
      return false;
    }
  }
  
  public function moveDate($dateFrom, $dateTo)
  {
    $id_distribution = $this->retrieveFromDate($dateFrom);
    if ($id_distribution === false)
      return false;
    else
    {
      $distri = $this->find($id_distribution);
      $distri->setDate(DateTime::createFromFormat('Y-m-d', $dateTo));
      $em = $this->getEntityManager();
      $em->persist($distri);
      $em->flush();
      return true;
    }
  }
  
  protected function retrieveFromDate($dateStr)
  {
    $conn = $this->getEntityManager()->getConnection();
    $sql = "SELECT id_distribution FROM distribution WHERE date='".$dateStr."'";
    $r = $conn->query($sql);
    return $r->fetch(\PDO::FETCH_COLUMN);
  }
  
  protected function addDistri($date)
  {
    $date = DateTime::createFromFormat('Y-m-d', $date);
    $distri = new Distribution();
    $distri->setDate($date);
    $em = $this->getEntityManager();
    $em->persist($distri);
    $em->flush();
  }
  
  protected function removeDistri($id_distribution)
  {
    $distri = $this->find($id_distribution);
    $em = $this->getEntityManager();
    $em->remove($distri);
    $em->flush();
  }
  
  public function findAllSorted()
  {
      return $this->createQueryBuilder('d')
              ->orderBy('d.date')
              ->getQuery()
              ->getResult();
  }
  
  public function findAllForCalendar()
  {
    $conn = $this->getEntityManager()->getConnection();
    //$conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
    $sql = "SELECT d.date, COUNT(pd.id_product_distribution) AS nb_product, COUNT(pu.id_purchase) AS nb_purchase
            FROM distribution d
            LEFT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
            LEFT JOIN purchase pu ON pu.fk_product_distribution =  pd.id_product_distribution
            GROUP BY d.id_distribution";
    $r = $conn->query($sql);
    return $r->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);
  }
  public function findAllForDistripicker()
  {
    $conn = $this->getEntityManager()->getConnection();
    $sql = "SELECT d.date, 1
            FROM distribution d
            ORDER BY d.date";
    $r = $conn->query($sql);
    return $r->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  private function loadAll() {
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT id_distribution, date
            FROM distribution            
            ORDER BY date ASC";
      $r = $conn->query($sql);
      $this->all = $r->fetchAll(\PDO::FETCH_KEY_PAIR);
  }

  public function findAllOffset($first, $nb)
  {
      if ($this->all == null) {
         $this->loadAll();
      }
      $next = null;
      $now = new \DateTime();
      $i = 0;
      $index = 0;
      foreach($this->all as $date) {
      $dt = \DateTime::createFromFormat('Y-m-d',$date);
      if ($dt > $now) {
          $next = $date;
          $index = $i;
          break;
      }
      $i++;
      }
      return array_slice($this->all,$index+$first,$nb,true);
  }



    public function findAllForStat() {
      $conn = $this->getEntityManager()->getConnection();
    $sql = 'SELECT distinct(date_format(date,"%Y%m"))
            FROM distribution
            WHERE date<=date_add(curdate(), INTERVAL 3 MONTH)
            AND date>=date_add(curdate(), INTERVAL -21 MONTH)
            ORDER BY date_format(date,"%Y%m") ASC';
    $r = $conn->query($sql);
    return $r->fetchAll(\PDO::FETCH_COLUMN);
  }
  //$dayOfWeek : 1=lundi, 2=mardi... 7=dimanche
  public function activeAllDayOfWeek($dayOfWeek, $fromDateStr, $toDateStr)
  {

      $from = \DateTime::createFromFormat('Y-m-d', $fromDateStr);
      $to = \DateTime::createFromFormat('Y-m-d', $toDateStr);
      $to->modify('+1 day');
      $interval = new \DateInterval('P1D');
      $daterange = new \DatePeriod($from, $interval ,$to);
      $nb = 0;
      foreach($daterange as $date)
      {
        if ($dayOfWeek == $date->format("N") || $dayOfWeek == 0)
        {
            $dateStr = $date->format("Y-m-d");
            $id_distribution = $this->retrieveFromDate($dateStr);
            if ($id_distribution === false)
            {
              $this->addDistri($dateStr);
              $nb++;
            }
        }
      }
      return $nb;
  }
  
  public function showProducts($date)
  {
    $sql = "SELECT CONCAT(ifnull(p.label,''), ' - ', ifnull(p.unit,''), ' (', ifnull(f.label,''), ')') AS produit
      FROM distribution d
      LEFT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
      LEFT JOIN product p ON p.id_product = pd.fk_product
      LEFT JOIN farm f ON f.id_farm = p.fk_farm
      WHERE d.date=:date";
    $conn = $this->getEntityManager()->getConnection();
    $stmt = $conn->prepare($sql);
    $stmt->execute(array('date' => $date));
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }
  
  public function getBetween($startDate, $startEnd)
  {
    $sql = "SELECT date 
            FROM distribution
            WHERE date BETWEEN :startDate AND :startEnd
            ORDER BY date";
    $conn = $this->getEntityManager()->getConnection();
    $stmt = $conn->prepare($sql);
    $stmt->execute(array('startDate' => $startDate,'startEnd'=>$startEnd));
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }
  
  public function findNextDate()
  {
    $sql = "SELECT date
          FROM distribution
          WHERE date>=date_sub(CURDATE(),interval 1 day)
          ORDER BY date ASC
          LIMIT 1";
    $conn = $this->getEntityManager()->getConnection();
    $r = $conn->query($sql);
    return $r->fetch(\PDO::FETCH_COLUMN);
  }
  
  public function findNDateFrom($date,$n)
  {
    $sql = "SELECT date 
        FROM distribution 
        WHERE date >= :date
        ORDER BY date ASC
        LIMIT ".$n;
    $conn = $this->getEntityManager()->getConnection();
    $stmt = $conn->prepare($sql);
    $stmt->execute(['date' => $date]);
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
  }
  
   public function getDistributionsForContract($id_contract) {
     $sql = "SELECT date 
            FROM distribution
            WHERE date 
            BETWEEN (select period_start from contract where id_contract=:id_contract) 
            AND (select period_end from contract where id_contract=:id_contract) 
            ORDER BY date";
    $conn = $this->getEntityManager()->getConnection();
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id_contract' => $id_contract]);
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    public function getMonthsForContract($id_contract) {
     $sql = "SELECT date_format(date, '%Y-%m')
            FROM distribution
            WHERE date 
            BETWEEN (select period_start from contract where id_contract=:id_contract) 
            AND (select period_end from contract where id_contract=:id_contract)
            group by date_format(date, '%Y-%m')
            ORDER BY date_format(date, '%Y-%m')";
    $conn = $this->getEntityManager()->getConnection();
    $stmt = $conn->prepare($sql);
    $stmt->execute(['id_contract' => $id_contract]);
    return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
    
    public function getInMonth($mois,$annee) {
        $sql = "SELECT date
            FROM distribution
            WHERE month(date)=:mois AND year(date)=:annee
            ORDER BY date";
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute(array('mois' => $mois, 'annee' => $annee));
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);    
    }
    
    public function getLasts($page, $nbPerPage) {
        $qb = $this->createQueryBuilder('d')
            ->addOrderBy('d.date', 'DESC')
            ->andWhere('d.date <= :now')
            ->setParameter('now',new \DateTime());
        $query = $qb->getQuery();
        $firstResult = ($page - 1) * $nbPerPage;
        $query->setFirstResult($firstResult)->setMaxResults($nbPerPage);
        $paginator = new Paginator($query);
        if (($paginator->count() <= $firstResult) && $page != 1) {
            throw new NotFoundHttpException('La page demandée n\'existe pas.'); // page 404, sauf pour la première page
        }
        return $paginator;   
    }
        
}