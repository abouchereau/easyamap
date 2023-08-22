<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use App\Entity\Participation;


class ParticipationController extends AmapBaseController
{    
    public function index($admin = false) {
        $this->denyAccessUnlessGranted('ROLE_ADHERENT');
        if ($admin) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }
        $em = $this->getDoctrine()->getManager();
              
        if (!$em->getRepository('App\Entity\Setting')->get('registerDistribution',$_SERVER['APP_ENV'])) {
            throw $this->createAccessDeniedException('Cette fonctionnalité n\'est pas activée');
        }
        
        $data = $em->getRepository('App\Entity\Participation')->getNext(6);
        $setting = $em->getRepository('App\Entity\Setting')->getFromCache($_SERVER['APP_ENV']);
        $available_tasks = $em->getRepository('App\Entity\Task')->getAvailable();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        return $this->render('Participation/index.html.twig', array(
            'data'      => $data,
            'setting'   => $setting,
            'available_tasks'     => $available_tasks,
            'current_user'     => $user,
            'admin' => $admin
        ));
    }
    

    
    public function add($id_distribution, $id_task, $id_user = null) {
        $this->denyAccessUnlessGranted('ROLE_ADHERENT');
        
        if ($id_user != null) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }        
        $em = $this->getDoctrine()->getManager();
        
        if (!$em->getRepository('App\Entity\Setting')->get('registerDistribution',$_SERVER['APP_ENV'])) {
            throw $this->createAccessDeniedException('Cette fonctionnalité n\'est pas activée');
        }
        
        $distribution = $em->getRepository('App\Entity\Distribution')->find($id_distribution);
        $task = $em->getRepository('App\Entity\Task')->find($id_task);
        if ($id_user == null) {
            $user = $this->get('security.token_storage')->getToken()->getUser();
        }
        else {
            $user = $em->getRepository('App\Entity\User')->find($id_user);
        }        
        $check = $em->getRepository('App\Entity\Participation')->findOneBy(array('fkUser'=>$user,'fkDistribution'=>$distribution,'fkTask'=>$task));
        if ($check!=null) {
            $this->get('session')->getFlashBag()->add('error', 'Cette participation a déjà été enregistrée');
        }
        else {
            $participation = new Participation();
            $participation->setFkDistribution($distribution);
            $participation->setFkTask($task);
            $participation->setFkUser($user);
            try {
                $em->persist($participation);
                $em->flush();
            }
            catch(\Exception $e) {
                $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'inscription : '.$e->getMessage());
                return $this->redirect($this->generateUrl('participation'));
            }
            $this->get('session')->getFlashBag()->add('notice', 'L\'inscription a été prise en compte.');
        }
        if ($id_user == null) {
            $redirect = $this->generateUrl('participation');
        } 
        else {
            $redirect = $this->generateUrl('participation_admin');
        }
        return $this->redirect($redirect);
    }
    
    public function remove($id_participation, $admin = false) {
        $this->denyAccessUnlessGranted('ROLE_ADHERENT');
        $em = $this->getDoctrine()->getManager();
              
        if (!$em->getRepository('App\Entity\Setting')->get('registerDistribution', $_SERVER['APP_ENV'])) {
            throw $this->createAccessDeniedException('Cette fonctionnalité n\'est pas activée');
        }
        
        $participation = $em->getRepository('App\Entity\Participation')->find($id_participation);
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if ($participation != null) {
            if ($participation->getFkUSer()->getIdUser() == $user->getIdUser() || $user->getIsAdmin()) {
                try {
                    $em->remove($participation);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('notice', 'La désinscription a été prise en compte.');
                    $this->get('session')->getFlashBag()->add('error', 'Merci d\'avertir afin de trouver un remplaçant (surtout si la date est proche). ');                
                }
                catch(\Exception $e) {
                    $this->get('session')->getFlashBag()->add('error', 'Un problème a eu lieu : '.$e->getMessage());
                }
            }
            else {
                $this->get('session')->getFlashBag()->add('error', 'Vous n\'avez pas les droits nécessaires pour effectuer cette action. ');                
            }
        }
        if (!$admin) {
            $redirect = $this->generateUrl('participation');
        } 
        else {
            $redirect = $this->generateUrl('participation_admin');
        }
        return $this->redirect($redirect);
    }
    
    public function modalUserChoice() {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository('App\Entity\User')->findBy(array('isActive'=>true),array('lastname'=>'ASC'));
        return $this->render('Participation/_modalUserChoice.html.twig', array('users'=>$users));
    }
}