<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Setting;
use App\Form\SettingType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
/**
 * Parameters controller.
 *
 */
class ParametersController extends AmapBaseController
{
    public function index() {        
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        return $this->render('Parameters/index.html.twig');
    }
    
    
        /**
     * Displays a form to edit an existing Contract entity.
     *
     */
    public function editSetting()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App\Entity\Setting')->findOneBy(array(),array('id'=>'DESC'));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Contract entity.');
        }
        $editForm = $this->createEditForm($entity);
       // $deleteForm = $this->createDeleteForm($id);

        return $this->render('Parameters/editSetting.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView()
        ));
    }
    
    
    private function createEditForm(Setting $entity)
    {

        $form = $this->createForm(SettingType::class, $entity, array(
            'action' => $this->generateUrl('setting_update'),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }

    
    public function updateSetting(Request $request)
    {
        
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Setting')->findOneBy(array(),array('id'=>'DESC'));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Setting entity.');
        }

        
       // $deleteForm = $this->createDeleteForm($id);
        $form = $this->createEditForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) 
        {
            $em->persist($entity);
            $em->flush();
            $em->getRepository('App\Entity\Setting')->updateCache($_SERVER['APP_ENV']);            
            $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
        }
        else 
        {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$form->getErrors(true, false));
        }

        return $this->redirect($this->generateUrl('setting_edit'));
    }
}