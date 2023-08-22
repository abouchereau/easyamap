<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        $em = $this->getDoctrine()->getManager();     
        $setting = $em->getRepository('App\Entity\Setting')->getFromCache($_SERVER['APP_ENV']);        
        $em->getRepository('App\Entity\Setting')->updateManifest($_SERVER['APP_ENV'],false);//on update le fichier que s'il n'existe pas


        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error,'setting' => $setting]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }
}
