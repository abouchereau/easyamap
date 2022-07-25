<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Distribution;

class DistributionController extends AmapBaseController
{
    const NB_PER_PAGE = 10;
    
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
    
    public function distripicker($urlTemplate, $date, $nb, $farms)
    {
        $em = $this->getDoctrine()->getManager();
        $distributions = $em->getRepository('App\Entity\Distribution')->findAllForDistripicker();
        return $this->render('Distribution/_distripicker.html.twig', array(
            'urlTemplate' => $urlTemplate,
            'distributions' => $distributions,
            'date' => $date,
            'nb' => $nb
            'farms' => $farms
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
    
    public function showRapport($id,$isEdit=false) {        
        if($isEdit && !$this->isEditable($id)) {
            throw new AccessDeniedException();
        }
        
        $em = $this->getDoctrine()->getManager();
        $distri = $em->getRepository('App\Entity\Distribution')->find($id); 
        $farms = $em->getRepository('App\Entity\ProductDistribution')->getFarmForDistribution($id); 
        $participations = $em->getRepository('App\Entity\Participation')->getTaskForDistributionAndNext($id); 
        $form = null;
        if($isEdit) {
            $form = $this->createEditForm($distri);
            $form = $form->createView();
        }
        
        return $this->render('Distribution/show.html.twig', [         
            'form' => $form,
            'isEdit' => $isEdit,
            'isEditable' => $this->isEditable($distri->getIdDistribution()),
            'distri' => $distri,
            'farms' => $farms,
            'participations' => $participations
        ]);
    }
    
    private function isEditable($id) {
        //Si admin ou si participant        
        //TODO date limite ?
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        return $user->getIsAdmin() || $em->getRepository('App\Entity\Participation')->isParticipant($user->getIdUser(),$id);
    }
    
    public function saveRapport(Request $request, $id) {
        if(!$this->isEditable($id)) {
            throw new AccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Distribution')->find($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Distribution entity.');
        }
        $form = $this->createEditForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) 
        {
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
        }
        else {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$form->getErrors(true, false));
        }
        
        return $this->redirect($this->generateUrl('rapport_distribution_show',['id'=>$id, 'edit'=>false]));
    }

    private function createEditForm(Distribution $entity) {
        $form = $this->createForm(\App\Form\DistributionType::class, $entity, array(
                'action' => $this->generateUrl('rapport_distribution_save', array('id' => $entity->getIdDistribution())),
                'method' => 'PUT'
            ));
        $form->add('submit', SubmitType::class, array('label' => 'Enregistrer'));
        return $form;
    }
 
}