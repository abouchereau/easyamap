<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Api controller.
 *
 */
class ApiController extends Controller
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