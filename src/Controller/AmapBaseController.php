<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;


class AmapBaseController extends AbstractController
{    
  
    public function preExecute(Request $request) {        
        //déblocage de l'isoloir sur les contrats

        $previous_url = $request->headers->get('referer');
        $user = $this->get('security.token_storage')->getToken()->getUser();        
        if (strpos($previous_url, '/contrat/') !== false && gettype($user)=="object") {
            $em = $this->getDoctrine()->getManager();
            $em->getRepository('App\Entity\Booth')->unlockContract($previous_url,$user);
        }
        
        //s'assurer que la session n'a pas expiré
        $session = new Session();
        if (!$session->has('roles') && gettype($user)=="object") {
            $em = $this->getDoctrine()->getManager();      
            $em->getRepository('App\Entity\User')->loadRoles($user);
        }
    }
    
}