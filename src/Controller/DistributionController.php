<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DistributionController extends AmapBaseController
{
    const NB_PER_PAGE = 20;
    
    public function index()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('App\Entity\Distribution')->findAllForCalendar();
        return $this->render('Distribution/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    
    public function toggleDate($date)
    {     
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em = $this->getDoctrine()->getManager();
        $active = $em->getRepository('App\Entity\Distribution')->toggle($date);
        return new Response($active?'active':'inactive');
    }
    
    public function moveDate($date_from, $date_to)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em = $this->getDoctrine()->getManager();
        $ok = $em->getRepository('App\Entity\Distribution')->moveDate($date_from, $date_to);
        return new Response($ok?'ok':'pb');
    }
    
    public function showProducts($date)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em = $this->getDoctrine()->getManager();
        $products = $em->getRepository('App\Entity\Distribution')->showProducts($date);
        return new Response(json_encode($products));
    }
    
    public function addBatch(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $day = $request->request->get('day');
        $date_from = $request->request->get('date_from');
        $date_to = $request->request->get('date_to');

        $match_date = '#^[0-9]{4}-[0-9]{2}-[0-9]{2}$#';
        if (preg_match('#^[0-7]{1}$#',$day) && preg_match($match_date,$date_from) && preg_match($match_date,$date_to))
        {
          $em = $this->getDoctrine()->getManager();
          $nb = $em->getRepository('App\Entity\Distribution')->activeAllDayOfWeek($day, $date_from, $date_to);
          $this->get('session')->getFlashBag()->add('notice', $nb.' distributions ont été ajoutées.');
        }
        else
        {
          $this->get('session')->getFlashBag()->add('error', 'Les données du formulaire ne sont pas correctes.');
        }
        return $this->redirect($this->generateUrl('distribution'));
    }
    
    public function getBetween($dateStart, $dateEnd)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $distris = $em->getRepository('App\Entity\Distribution')->getBetween($dateStart, $dateEnd);
        return new Response(json_encode($distris));
    }
    
    public function distripicker($urlTemplate, $date, $nb)
    {
        $em = $this->getDoctrine()->getManager();
        $distributions = $em->getRepository('App\Entity\Distribution')->findAllForDistripicker();
        return $this->render('Distribution/_distripicker.html.twig', array(
            'urlTemplate' => $urlTemplate,
            'distributions' => $distributions,
            'date' => $date,
            'nb' => $nb
        ));
    }
    
    public function list($page=1) {
        $em = $this->getDoctrine()->getManager();
        $distris = $em->getRepository('App\Entity\Distribution')->getLasts($page, self::NB_PER_PAGE);        

        $pagination = [
            'page' => $page,
            'nbPages' => ceil(count($distris) / self::NB_PER_PAGE),
            'paramsRoute' => []
        ];
        return $this->render('Distribution/list.html.twig', [
            'distris' => $distris,
            'pagination' => $pagination
        ]);
    }
    
    public function showRapport($id) {
        $em = $this->getDoctrine()->getManager();
        $distri = $em->getRepository('App\Entity\Distribution')->find($id); 
        //TODO produits livrés
        $products = $em->getRepository('App\Entity\ProductDistribution')->getProductsForDistribution($id); 
        //TODO liste personnes cette distribution + suivante 
        
        return $this->render('Distribution/show.html.twig', [
            'distri' => $distri
        ]);
    }
    
    public function editRapport($id) {
        
    }
    
 
}