<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Api controller.
 *
 */
class ApiController extends AbstractController
{
    public function getAllFarms() {
        $em = $this->getDoctrine()->getManager();
        $farms = $em->getRepository('App\Entity\Farm')->getAllFarms();
        return new JsonResponse($farms);
    }

    public function getAllProducts() {
        $em = $this->getDoctrine()->getManager();
        $products = $em->getRepository('App\Entity\Product')->getAllProducts();
        return new JsonResponse($products);
    }
}