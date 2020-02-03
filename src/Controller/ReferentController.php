<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

use App\Entity\Referent;
use App\Form\ReferentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
/**
 * Referent controller.
 *
 */
class ReferentController extends AmapBaseController
{

    /**
     * Lists all Referent entities.
     *
     */
    public function index()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('App\Entity\Referent')->findAll();

        return $this->render('Referent/index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Referent entity.
     *
     */
    public function create(Request $request)
    {
        $entity = new Referent();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
            return $this->redirect($this->generateUrl('referent'));
        }
        else {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$form->getErrors(true, false));
        }

        return $this->render('Referent/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Referent entity.
     *
     * @param Referent $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Referent $entity)
    {
        $form = $this->createForm(new ReferentType(), $entity, array(
            'action' => $this->generateUrl('referent_create'),
            'method' => 'POST',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Referent entity.
     *
     */
    public function new()
    {
        $entity = new Referent();
        $form   = $this->createCreateForm($entity);

        return $this->render('Referent/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }



    /**
     * Displays a form to edit an existing Referent entity.
     *
     */
    public function edit($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App\Entity\Referent')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Referent entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Referent/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Referent entity.
    *
    * @param Referent $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Referent $entity)
    {
        $form = $this->createForm(new ReferentType(), $entity, array(
            'action' => $this->generateUrl('referent_update', array('id' => $entity->getIdReferent())),
            'method' => 'PUT',
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Referent entity.
     *
     */
    public function update(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App\Entity\Referent')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Referent entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
            return $this->redirect($this->generateUrl('referent_edit', array('id' => $id)));
        }
        else
        {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$editForm->getErrors(true, false));
        }

        return $this->render('Referent/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Referent entity.
     *
     */
    public function delete(Request $request, $id)
    {

        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Referent')->find($id);

        if (!$entity) {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de la suppression');
            throw $this->createNotFoundException('Unable to find Referent entity.');
        }
        else
        {
            $this->get('session')->getFlashBag()->add('notice', 'L\'élément a été supprimé.');
        }

        $em->remove($entity);
        $em->flush();


        return $this->redirect($this->generateUrl('referent'));
    }

    /**
     * Creates a form to delete a Referent entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->set($this->generateUrl('referent_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }
}
