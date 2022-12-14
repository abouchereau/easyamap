<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Session\Session;
/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function tryAuthenticate($username,$password)
    { 

      try {    
            $user = $this->createQueryBuilder('u')
            ->where('UPPER(u.username) =:username')
            ->andWhere('u.password = :password')
            ->andWhere('u.isActive = 1')
            ->setParameter('username', mb_strtoupper($username, 'UTF-8'))
            ->setParameter('password', $password)
            ->getQuery()
            ->getOneOrNullResult();          
        } catch (Exception $e) {            
            $message = sprintf(
                'Unable to find an active User object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message, 0, $e);
        }        

        return $user;
    }
    
    
    public function updateLastConnection($user)
    {
        $user->setLastConnection(new \DateTime());
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();
    }
    
    public function loadRoles($user) {
        
        //1: user,2: adherent, 3:referent, 4:farmer, 5:admin
        $rolesStr = '1';
        $em = $this->getEntityManager();
        if (is_object($user)) {
            
            if ($user->getIsAdherent()) {
                $rolesStr .= '2';
            }
            
            $ref = $em->getRepository('App\Entity\Referent')->findOneBy(array('fkUser'=>$user));

            if ($ref != null)
                $rolesStr .= '3';

            $farm = $em->getRepository('App\Entity\Farm')->findOneBy(array('fkUser'=>$user));
            if ($farm != null)
                $rolesStr .= '4';

            if ($user->getIsAdmin()) {            
                $rolesStr .= '5';            
            }
        }
        $session = new Session();
        $session->set('roles',$rolesStr);
    }
    
    public function getAllUsers()
    {
        $q = $this
            ->createQueryBuilder('u')
            ->where('u.isActive=1')
            ->getQuery();

        try {
            // The Query::getSingleResult() method throws an exception
            // if there is no record matching the criteria.
            $users = $q->getResult();
        } catch (NoResultException $e) {
            $message = sprintf(
                'Unable to find an active admin AmapOrderBundle:User object identified by "%s".',
                $username
            );
            throw new UsernameNotFoundException($message, 0, $e);
        }

        return $users;
    }
    

    public function refreshUser(UserInterface $user)
    {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
                sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                )
            );
        }

        return $this->find($user->getIdUser());
    }

    public function supportsClass($class)
    {
        return $this->getEntityName() === $class
            || is_subclass_of($class, $this->getEntityName());
    }
    
    public function findAllOrderByLastname()
    {
       return $this
        ->createQueryBuilder('u')
         ->addOrderBy('u.isActive', 'DESC')
         ->addOrderBy('u.lastname')
         ->getQuery()
         ->getResult();
    }
    
    public function canBeDeleted($id_user)
    {
      //on regarde si l'utilisateur apparaît dans d'autres tables
      $conn = $this->getEntityManager()->getConnection();
      $sql = "SELECT COUNT(fk_user) AS nb FROM view_deletable_user WHERE fk_user=:id_user";
      $query = $conn->executeQuery($sql, array('id_user' => $id_user));
      return $query->fetchColumn()==1;
    }
    
    public function getUsersWithoutPassword()
    {
       return $this
         ->createQueryBuilder('u')
         ->where('u.password IS NULL')
         ->orWhere("u.password =''")
         ->orWhere("u.password='achanger'")
         ->getQuery()
         ->getResult();
    }
    
    public function getUsers4Test() {
        return $this
         ->createQueryBuilder('u')
         ->where('u.idUser=12')         
         ->getQuery()
         ->getResult();
    }
}