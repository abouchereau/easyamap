<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;


class TaskRepository extends EntityRepository 
{
    public function getAvailable() {
        $sql = "select id_task, label, min, max
            from task
            where is_active=1
            order by id_task";
        $conn = $this->getEntityManager()->getConnection();
        $r = $conn->query($sql);
        return $r->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);
    }
}