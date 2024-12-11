<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

/**
 * WebhookController
 *
 */
class WebhookController extends Controller
{
    public function accountLinkExpiration(Request $request, LoggerInterface $logger) {
        $logger->info(print_r($request->request->all(),1));
        return $this->render('Home/standard_page.html.twig', ["title"=>"Création", "body"=>"Le lien est expiré"]);
    }

    public function accountLinkComplete(Request $request, LoggerInterface $logger) {
        $logger->info(print_r($request->request->all(),1));
        return $this->render('Home/standard_page.html.twig', ["title"=>"Création", "body"=>"Le compte a été créé avec succès"]);
    }
}