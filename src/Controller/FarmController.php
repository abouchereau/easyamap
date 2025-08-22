<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Farm;
use App\Form\FarmType;
use App\Api\StripeManager;
use Symfony\Component\Routing\Generator\UrlGenerator;
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
        $this->denyAccessUnlessGranted(['ROLE_REFERENT','ROLE_FARMER']);
        
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
        $this->denyAccessUnlessGranted(['ROLE_REFERENT','ROLE_FARMER']);
        
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

    public function compteBancaire($id) {
        $this->denyAccessUnlessGranted(['ROLE_FARMER','ROLE_ADHERENT']);
        $em = $this->getDoctrine()->getManager();
        $farm = $em->getRepository('App\Entity\Farm')->find($id);
        //TODO vérifier que l'utilisateur a droit
        if ($farm->getStripeAccountId()==null) {
            return $this->render('Farm/compte_bancaire_1.html.twig', ['farm'=>$farm]);
        }
        else {   
            $stripe = new StripeManager();
            $account = $stripe->getAccount($farm->getStripeAccountId());

            $active = $account['capabilities']!=null && $account['capabilities']['transfers'];
            $account_link = "";
            

            if (true) {//!$active) {//enlever ce champ, vérifier si le compte est actif ou non
                $refreshUrl = $this->generateUrl('account_link_expiration', [], UrlGenerator::ABSOLUTE_URL);
                $returnUrl = $this->generateUrl('account_link_complete', [], UrlGenerator::ABSOLUTE_URL);
                $account_link = $stripe->createAccountLink($farm->getStripeAccountId(), $refreshUrl, $returnUrl);
     //die(print_r($account_link,1));
            }
/**
 * Stripe\Account Object
(
    [id] => acct_1QUTN0QxERCZWboI
    [object] => account
    [business_profile] => Stripe\StripeObject Object
        (
            [annual_revenue] => 
            [estimated_worker_count] => 
            [mcc] => 5734
            [name] => Easyamap Assoc
            [product_description] => Applications commandes pour AMAP
            [support_address] => 
            [support_email] => anthony@easyamap.fr
            [support_phone] => 0664309539
            [support_url] => 
            [url] => 
        )

    [business_type] => company
    [capabilities] => Stripe\StripeObject Object
        (
            [card_payments] => active
            [transfers] => active
        )

 */

            return $this->render('Farm/compte_bancaire_2.html.twig', ['active'=>$active, 'account_link'=>$account_link]);
        }
    }

    public function stripeCreateAccount(Request $request) {
        $this->denyAccessUnlessGranted(['ROLE_FARMER','ROLE_ADHERENT']);
        $data = json_decode($request->getContent(),true);
        if ($data['token']!=null && $data['tel']!=null && $data['email']!=null && $data['id_farm']!=null) {
            $stripe = new StripeManager();           
            $em = $this->getDoctrine()->getManager(); 
            $farm = $em->getRepository('App\Entity\Farm')->find($data['id_farm']);
            $account_id = $stripe->createAccount($farm, $data['token'], $data['tel'],$data['email']);
            $farm->setStripeAccountId($account_id);
            $em->persist($farm);
            $em->flush();   
            return new Response("ok");
        }
        return new Response("ko");
    }
    
}
