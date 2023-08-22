<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\User;

class ParticipationRepository extends EntityRepository 
{
    public function getNext($nb_month) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select date_format(date,'%Y-%m') as month, t.id_task, d.id_distribution, d.date, u.id_user, u.lastname, p.id_participation
                from participation p
                left join user u on u.id_user = p.fk_user
                right join distribution d on d.id_distribution = p.fk_distribution
                left join task t on t.id_task = p.fk_task
                where d.date between now() - interval 3 day and last_day(now() + interval :nb_month month) 
                order by d.date asc, t.id_task";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['nb_month' => $nb_month]);
        $tab = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $all = array();
        foreach($tab as $t) {
            if (!isset($all[$t['month']])) {
                $all[$t['month']] = array();
            }
            if (!isset($all[$t['month']]['dates'])) {
                $all[$t['month']]['dates'] = array();
            }
            if (!in_array(array('id_distribution'=>(int)$t['id_distribution'],'date'=>$t['date']),$all[$t['month']]['dates'])) {
                $all[$t['month']]['dates'][] = array('id_distribution'=>(int)$t['id_distribution'],'date'=>$t['date']);
            }
            if (!isset($all[$t['month']]['tasks'])) {
                $all[$t['month']]['tasks'] = array();
            }
            if (!isset($all[$t['month']]['tasks'][(int)$t['id_task']])) {
                $all[$t['month']]['tasks'][(int)$t['id_task']] = array();            
            }
            if (!isset($all[$t['month']]['tasks'][(int)$t['id_task']][(int)$t['id_distribution']])) {
                $all[$t['month']]['tasks'][(int)$t['id_task']][(int)$t['id_distribution']] = array();
            }
            if ($t['lastname'] != null) {
                $all[$t['month']]['tasks'][(int)$t['id_task']][(int)$t['id_distribution']][] = array('name'=>$t['lastname'], 'id'=>(int)$t['id_user'],'id_participation'=>$t['id_participation']);
            }
        }
        return $all;
    }
        

    
    public function getTasks($dates, $id_user=null) {
        $params = [];
        $sql = "SELECT CONCAT(ifnull(u.lastname,''),'<br>',ifnull(u.firstname,'')) AS user, 
            DATE_FORMAT(d.date,'%Y-%m-%d') as date, 
            t.label
            from participation p
            left join user u on u.id_user = p.fk_user
            left join task t on p.fk_task = t.id_task
            left join distribution d on d.id_distribution=p.fk_distribution
            where d.date IN ('".implode("','",$dates)."')";
        if ($id_user != null) {
            $sql .= " and fk_user=:id_user";
            $params['id_user'] = $id_user;
        }
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $tab = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $out = [];
        foreach($tab as $each) {
            if (!isset($out[$each['user']])) {
                $out[$each['user']] = [];
            }
            if (!isset($out[$each['user']][$each['date']])) {
                $out[$each['user']][$each['date']] = [];
            }
            $out[$each['user']][$each['date']][] = $each['label'];
        }
        return $out;
    }
    
    public function getTaskForDistributionAndNext($id_distribution) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select t.id_distribution, t.date, t.label tache,  GROUP_CONCAT(CONCAT(u.lastname, ' ', u.firstname) SEPARATOR ', ') inscrits
from task t
left join participation p on t.id_task = p.fk_task
left join user u on p.fk_user = u.id_user
inner join (
    select id_distribution, date
    from distribution
    where date >= (select date from distribution where id_distribution = :id_distribution)
    order by date asc
    limit 2) t on t.id_distribution=p.fk_distribution
group by t.id_distribution, t.label, t.date, t.id_task
order by t.date, t.id_task";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_distribution'=>$id_distribution]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC); 

    }
    
    public function isParticipant($id_user, $id_distribution) {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "select count(*) as nb from participation where fk_distribution=:id_distribution and fk_user=:id_user";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id_distribution'=>$id_distribution,':id_user'=>$id_user]);
        return $stmt->fetch(\PDO::FETCH_COLUMN) > 0; 
    }
            
}