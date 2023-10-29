<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Product;
use App\Form\ProductType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
/**
 * Product controller.
 *
 */
class ProductController extends AmapBaseController
{

    /**
     * Lists all Product entities.
     *
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        if ($user->getIsAdmin())//admin : on voit tous les produits
          $entities = $em->getRepository('App\Entity\Product')->findAllOrderByFarm();
        elseif ($user->isReferent())//référent : on voit les produits des fermes pour lesqueslles on est référent
          $entities = $em->getRepository('App\Entity\Product')->findAllForReferent($user);

        $farms = [];
        foreach ($entities as $entity) {
            $farmName = $entity->getFkFarm()->getLabel();
            if (!in_array($farmName, $farms)) {
                $farms[] = $farmName;
            }
        }
        sort($farms);

        return $this->render('Product/index.html.twig', array(
            'entities' => $entities,
            'farms' => $farms
        ));
    }
    /**
     * Creates a new Product entity.
     *
     */
    public function create(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $entity = new Product();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) 
        {
            $entity->setCreatedNow();
            $entity->setSequenceAtEnd();
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $em->getRepository('App\Entity\Product')->restoreSequencing();
            $this->get('session')->getFlashBag()->add('notice', 'Le produit a été ajouté.');
            return $this->redirect($this->generateUrl('product'));
        }
        else
        {
          $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$form->getErrors(true, false));
        }

        return $this->render('Product/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Product entity.
     *
     * @param Product $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Product $entity)
    {
        $form = $this->createForm(ProductType::class, $entity, array(
            'action' => $this->generateUrl('product_create'),
            'method' => 'POST',
            'user' => $user = $this->get('security.token_storage')->getToken()->getUser()
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Product entity.
     *
     */
    public function new($id = null)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $entity = new Product();
        if ($id != null) {//dupliquer
            $em = $this->getDoctrine()->getManager();
            $initial_product = $em->getRepository('App\Entity\Product')->find($id);
            if ($initial_product != null) {
                $entity->setLabel($initial_product->getLabel());
                $entity->setFkFarm($initial_product->getFkFarm());
                $entity->setUnit($initial_product->getUnit());
                $entity->setDescription($initial_product->getDescription());
                $entity->setBasePrice($initial_product->getBasePrice());
                $entity->setIsSubscription($initial_product->getIsSubscription());
                $entity->setIsCertified($initial_product->getIsCertified());
            }
        }

        $form   = $this->createCreateForm($entity);

        return $this->render('Product/new.html.twig', array(
            'id' => $id,
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }



    /**
     * Displays a form to edit an existing Product entity.
     *
     */
    public function edit($id)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App\Entity\Product')->find($id);
        $canBeDeleted = $em->getRepository('App\Entity\Product')->canBeDeleted($id);
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
        }

        $editForm = $this->createEditForm($entity);

        return $this->render('Product/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'can_be_deleted' => $canBeDeleted
        ));
    }

    /**
    * Creates a form to edit a Product entity.
    *
    * @param Product $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Product $entity)
    {
        $form = $this->createForm(ProductType::class, $entity, array(
            'action' => $this->generateUrl('product_update', array('id' => $entity->getIdProduct())),
            'method' => 'PUT',
            'user' => $user = $this->get('security.token_storage')->getToken()->getUser()
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Product entity.
     *
     */
    public function update(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App\Entity\Product')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Product entity.');
        }
        $canBeDeleted = $em->getRepository('App\Entity\Product')->canBeDeleted($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $entity->setUpdatedNow();            
            $em->flush();
            $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');            
            return $this->redirect($this->generateUrl('product_edit', array('id' => $id)));
        }        
        else {
          $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$editForm->getErrors(true, false));
        }
        

        return $this->render('Product/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'can_be_deleted' => $canBeDeleted
        ));
    }
    /**
     * Deletes a Product entity.
     *
     */
    public function delete(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Product')->find($id);

        if (!$entity) {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de la suppression');
            throw $this->createNotFoundException('Unable to find Product entity.');
        } else {
          $this->get('session')->getFlashBag()->add('notice', 'L\'élément a été supprimé.');
        }

        $em->remove($entity);
        $em->flush();
        $em->getRepository('App\Entity\Product')->restoreSequencing();

        return $this->redirect($this->generateUrl('product'));
    }
    
    public function activate($id, $active)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Product')->find($id);
        $entity->setIsActive($active);
        $entity->setUpdatedNow();
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('product_edit',array('id' => $id)));
    }
    
    public function ajaxProductChangeOrder($id_from, $id_before, $id_after) {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        $em = $this->getDoctrine()->getManager();
        $v = $em->getRepository('App\Entity\Product')->changeOrder($id_from, $id_before, $id_after);         
        return new Response($v?':)':':(');
    }
}
