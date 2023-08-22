<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Farm;
use App\Form\FarmType;

/**
 * Farm controller.
 *
 */
class FarmController extends AmapBaseController
{

    /**
     * Lists all Farm entities.
     *
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $entities = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);
        if (!$user->getIsAdmin() && count($entities) == 1)
        {
            return $this->redirect($this->generateUrl('farm_edit',array('id'=>$entities[0]->getIdFarm())));
        }
        return $this->render('Farm/index.html.twig', array(
                'entities' => $entities,
                'user' => $user
            ));

        
    }
    /**
     * Creates a new Farm entity.
     *
     */
    public function create(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $entity = new Farm();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->setSequenceAtEnd();
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $em->getRepository('App\Entity\Farm')->restoreSequencing();
            $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
            return $this->redirect($this->generateUrl('farm'));
        }
        else {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$form->getErrors(true, false));
        }

        return $this->render('Farm/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Farm entity.
     *
     * @param Farm $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Farm $entity)
    {
        $form = $this->createForm(FarmType::class, $entity, array(
            'action' => $this->generateUrl('farm_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Farm entity.
     *
     */
    public function new()
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $entity = new Farm();
        $form   = $this->createCreateForm($entity);

        return $this->render('Farm/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Farm entity.
     *
     */
    public function edit($id)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Farm')->find($id);
        $user = $this->get('security.token_storage')->getToken()->getUser();
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Farm entity.');
        }
        
        $canBeDeleted = $em->getRepository('App\Entity\Farm')->canBeDeleted($id);
        $editForm = $this->createEditForm($entity);
      //  $deleteForm = $this->createDeleteForm($id);

        return $this->render('Farm/edit.html.twig', array(
            'user'     => $user,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'can_be_deleted' => $canBeDeleted
           // 'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Farm entity.
    *
    * @param Farm $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Farm $entity)
    {
        $form = $this->createForm(FarmType::class, $entity, array(
            'action' => $this->generateUrl('farm_update', array('id' => $entity->getIdFarm())),
            'method' => 'PUT',
        ));
        
        //un référent ne peut pas changer de réfénrent
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->getIsAdmin())
            $form->remove('referents');
        
        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Farm entity.
     *
     */
    public function update(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Farm')->find($id);
        $user = $this->get('security.token_storage')->getToken()->getUser();
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Farm entity.');
        }
        $canBeDeleted = $em->getRepository('App\Entity\Farm')->canBeDeleted($id);
       // $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
            return $this->redirect($this->generateUrl('farm_edit', array('id' => $id)));
        }
        else {
          $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$editForm->getErrors(true, false));
        }
        
        return $this->render('Farm/edit.html.twig', array(
            'user'     => $user,
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'can_be_deleted' => $canBeDeleted
           // 'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Farm entity.
     *
     */
    public function delete(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Farm')->find($id);

        if (!$entity) {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de la suppression');
            throw $this->createNotFoundException('Unable to find Farm entity.');
        } else {
          $this->get('session')->getFlashBag()->add('notice', 'L\'élément a été supprimé.');
        }
        $em->remove($entity);
        $em->flush();


        return $this->redirect($this->generateUrl('farm'));
    }
    
    public function activate($id, $active)
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Farm')->find($id);
        $entity->setIsActive($active);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('farm_edit',array('id' => $id)));
    }
    
    public function ajaxFarmChangeOrder($id_from, $id_before, $id_after) {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        $em = $this->getDoctrine()->getManager();
        $v = $em->getRepository('App\Entity\Farm')->changeOrder($id_from, $id_before, $id_after);        
        return new Response($v?':)':':(');
    }

    
}
