<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use App\Entity\Contract;
use App\Form\ContractType;
use App\Util\Utils;
use App\Entity\User;

/**
 * Contract controller.
 *
 */
class ContractController extends AmapBaseController
{

    /**
     * Lists all Contract entities.
     *
     */
    public function index()
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');

        $session = new Session();
        $session->set('role', 'ROLE_REFERENT');

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        
        $entities = $em->getRepository('App\Entity\Contract')->findAllOrderByIdDesc($user);
        $nb_purchaser = $em->getRepository('App\Entity\Contract')->nbPurchaser();
        return $this->render('Contract/index.html.twig', array(
            'entities' => $entities,
            'nb_purchaser' => $nb_purchaser,
            'user' => $user
        ));
    }
    
    public function indexFarmer() {
        $this->denyAccessUnlessGranted(User::ROLE_FARMER);
        $session = new Session();
        $session->set('role',User::ROLE_FARMER);
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        
        $entities = $em->getRepository('App\Entity\Contract')->findAllOrderByIdDesc($user, true);
        
        return $this->render('Contract/indexFarmer.html.twig', array(
            'entities' => $entities,            
            'user' => $user
        ));
    }
    /**
     * Creates a new Contract entity.
     *
     */
    public function create(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
   
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $entity = new Contract();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        //$values = $request->request->get('amap_orderbundle_contract');
        $values = $request->request->all()['contract'];
        $entity->setPeriodStart(\DateTime::createFromFormat('Y-m-d', trim($values['periodStart'])));
        $entity->setPeriodEnd(\DateTime::createFromFormat('Y-m-d', trim($values['periodEnd'])));
        $entity->setFkUser($user);

        if ($form->isValid()) {      
            $em->persist($entity);
            $em->flush();
            
            //obsolète : détection des confilts
          /*  $conflicts = $em->getRepository('App\Entity\Contract')->getOverlappingContractsWithSameProducts($entity->getIdContract());
            if (true)//count($conflicts)==0)
            {
              $em->getConnection()->commit();
              $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
              return $this->redirect($this->generateUrl('contract_edit',array('id'=>$entity->getIdContract())));
            }
            else
            {
              $msg = 'Des conflits ont été détectés : certains produits d\'un autre contrat sont disponibles aux mêmes distributions. <table class="table"><tr><th>Contrat</th><th>Distribution</th><th>Produit</th></tr>';
              foreach($conflicts as $conflict)
              {
                $msg .= '<tr><td>'.$conflict['contrat'].'</td><td>'.$conflict['distribution'].'</td><td>'.$conflict['produit'].'</td></tr>';
              }
              $msg .= '</table>';
              $this->get('session')->getFlashBag()->add('error', $msg);
            }*/
            
            //détection de l'incompatabilité entre LIssage des paiement et prix au poids
            if ($em->getRepository('App\Entity\Contract')->hasEquitableAndRatio($entity)) {
                $this->get('session')->getFlashBag()->add('error', "Un problème a été détecté : il n'est pas possible d'avoir l'option \"Lissage de paiements\" avec des produits ayant un prix au poids.");
            }
            else {
                $em->getConnection()->commit();
                $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
                return $this->redirect($this->generateUrl('contract_edit',array('id'=>$entity->getIdContract())));
            }
            

        }
        else {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$form->getErrors(true, false));
        }
        $em->getConnection()->rollback();
        return $this->render('Contract/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Contract entity.
     *
     * @param Contract $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Contract $entity)
    {
      $user = $this->get('security.token_storage')->getToken()->getUser();
      
        $form = $this->createForm(\App\Form\ContractType::class, $entity, array(
            'action' => $this->generateUrl('contract_create'),
            'method' => 'POST',
            'user' => $user,
            'is_new' => true
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Contract entity.
     *
     */
    public function new($id = null)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        $entity = new Contract();               
        if ($id != null) {//dupliquer
            $em = $this->getDoctrine()->getManager();
            $initial_contract = $em->getRepository('App\Entity\Contract')->find($id);
            if ($initial_contract != null) {
                $entity->setLabel($initial_contract->getLabel());
                foreach ($initial_contract->getProducts() as $product) {
                    $entity->addProduct($product);
                }
            }
        }
        $form   = $this->createCreateForm($entity);

        return $this->render('Contract/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }



    /**
     * Displays a form to edit an existing Contract entity.
     *
     */
    public function edit($id)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('App\Entity\Contract')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Contract entity.');
        }
        
        $user = $this->get('security.token_storage')->getToken()->getUser();
        if (!$user->getIsAdmin() && $user->getIdUser() != $entity->getFkUser()->getIdUser()) {
            throw new AccessDeniedException();
        }
        
        $canBeDeleted = $em->getRepository('App\Entity\Contract')->canBeDeleted($id);
        $productPurchased = $em->getRepository('App\Entity\Contract')->getProductPurchased($id);
        $nbProductAvailable = $em->getRepository('App\Entity\Contract')->getNbProductAvailable($id);
        $editForm = $this->createEditForm($entity);
       // $deleteForm = $this->createDeleteForm($id);

        return $this->render('Contract/edit.html.twig', array(
            'entity'      => $entity,
            'form'   => $editForm->createView(),
            'can_be_deleted' => $canBeDeleted,
            'product_purchased' => $productPurchased,
            'nb_product_available' => $nbProductAvailable
           // 'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Contract entity.
    *
    * @param Contract $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Contract $entity)
    {

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $form = $this->createForm(\App\Form\ContractType::class, $entity, array(
            'action' => $this->generateUrl('contract_update', array('id' => $entity->getIdContract())),
            'method' => 'PUT',
            'user' => $user,
            'is_new' => false,
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Contract entity.
     *
     */
    public function update(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $em->getConnection()->beginTransaction();
        $entity = $em->getRepository('App\Entity\Contract')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Contract entity.');
        }

        
       // $deleteForm = $this->createDeleteForm($id);
        $form = $this->createEditForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) 
        {
            $em->persist($entity);
            $em->flush();
              
            $conflicts = $em->getRepository('App\Entity\Contract')->getOverlappingContractsWithSameProducts($entity->getIdContract());
            if (true)//count($conflicts)==0)
            {
              $em->getConnection()->commit();              
              $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
              return $this->redirect($this->generateUrl('contract_edit',array('id'=> $id)));
            }
            else
            {
              $msg = 'Des conflits ont été détectés : certains produits d\'un autre contrat sont disponibles aux mêmes distributions. <table class="table"><tr><th>Contrat</th><th>Distribution</th><th>Produit</th></tr>';
              foreach($conflicts as $conflict)
              {
                $msg .= '<tr><td>'.$conflict['contrat'].'</td><td>'.$conflict['distribution'].'</td><td>'.$conflict['produit'].'</td></tr>';
              }
              $msg .= '</table>';
              $this->get('session')->getFlashBag()->add('error', $msg);
            }
        }
        else 
        {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$form->getErrors(true, false));
        }

        $em->getConnection()->rollback();
        return $this->redirect($this->generateUrl('contract_edit',array('id'=> $id)));
        /*
        $canBeDeleted = $em->getRepository('App\Entity\Contract')->canBeDeleted($id);
        return $this->render('Contract/edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $form->createView(),
            'can_be_deleted' => $canBeDeleted
           // 'delete_form' => $deleteForm->createView(),
        ));*/
    }
    /**
     * Deletes a Contract entity.
     *
     */
    public function delete(Request $request, $id)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Contract')->find($id);

        if (!$entity) {
            $this->get('session')->getFlashBag()->add('error', 'Problème lors de la suppression');
            throw $this->createNotFoundException('Unable to find Contract entity.');
        } else {
          $this->get('session')->getFlashBag()->add('notice', 'L\'élément a été supprimé.');
        }

        $em->remove($entity);
        $em->flush();


        return $this->redirect($this->generateUrl('contract_index'));
    }

    /**
     * Creates a form to delete a Contract entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    /*private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->set($this->generateUrl('contract_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', SubmitType::class, array('label' => 'Delete'))
            ->getForm()
        ;
    }*/
    
    public function activate($id_contract, $bool)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Contract')->find($id_contract);
        $entity->setIsActive($bool);
        $em->persist($entity);
        $em->flush();
        return $this->redirect($this->generateUrl('contract_index'));
    }
    
    public function calendar($id_contract)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $entity        = $em->getRepository('App\Entity\Contract')->find($id_contract);
        $products      = $em->getRepository('App\Entity\Contract')->getProductsForCalendar($id_contract);
        $distributions = $em->getRepository('App\Entity\Contract')->getDistributions($id_contract);
        $calendar      = $em->getRepository('App\Entity\Contract')->getAvailabilities($id_contract);
        $nb_per_month = Utils::getNbPerMonth($distributions);  
        return $this->render('Contract/calendar.html.twig', array(
              'entity'        => $entity,
              'products'        => $products,
              'distributions' => $distributions,
              'nb_per_month'  => $nb_per_month,
              'calendar'      => $calendar
          ));
    }
    
    public function purchasers($id_contract)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('App\Entity\Contract')->find($id_contract);
        $purchasers = $em->getRepository('App\Entity\Contract')->getPurchasers($id_contract);
        return $this->render('Contract/purchasers.html.twig', array(
              'entity'      => $entity,
              'purchasers'   => $purchasers
          ));
    }
    
    public function report($id_contract, $id_farm=null, $type=null) {
        $this->denyAccessUnlessGranted(array('ROLE_REFERENT','ROLE_FARMER'));   
        
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $contract = $em->getRepository('App\Entity\Contract')->find($id_contract);
        $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);
        $contract_farms = $em->getRepository('App\Entity\Farm')->findForContract($id_contract);
        $farm = null;
        if ($id_farm != null)
            $farm = $em->getRepository('App\Entity\Farm')->find($id_farm);
        //on vérifie si la farm passée en paramètre est autorisée
        if ($id_farm != null && !$user->getIsAdmin()) { 
            $id_farms = array();
            foreach ($farms as $each_farm) {
                $id_farms[] = $each_farm->getIdFarm();
            }
            if (!in_array($id_farm, $id_farms)) {
                throw new AccessDeniedException();
            }
        }
        
        //on vérifie que le contrat a une farm autorisée
        if (!$user->getIsAdmin()) {
            $ok = false;
            foreach ($farms as $each_farm) {
                if (isset($contract_farms[$each_farm->getIdFarm()])) {
                    $ok = true;
                    $first_id_farm = $each_farm->getIdFarm();// #25 : problème
                    break;
                }
            }
            if (!$ok) {
                throw new AccessDeniedException();
            }
            
            //si pas de farm passée en paramètre, prendre la 1e de la liste (#29)
            if($id_farm == null) {
                $id_farm = $first_id_farm;
            }
        }
        
        //une seule farm
        $nb_farm = 0;
        $last_farm;
        foreach ($farms as $each_farm) {
            if (isset($contract_farms[$each_farm->getIdFarm()])) {
                $nb_farm++;
                $last_farm = $each_farm;
            }
        }
        if ($farm == null && $nb_farm==1) {
            $farm = $last_farm;
        }  
      
        $report  = null;
        $payment = null;
        $dates   = null;
        $nb_per_month = null;
        if ($type=="report") {
            $report = $em->getRepository('App\Entity\Contract')->getReport($id_contract, $id_farm);
            $payment = $em->getRepository('App\Entity\Payment')->getForContract($id_contract, $id_farm);
        }
        elseif ($type == "shipping") {
            $report = $em->getRepository('App\Entity\Contract')->getShipping($id_contract, $id_farm);
            $payment = $em->getRepository('App\Entity\Contract')->getShippingPayment($id_contract, $id_farm);
            $dates = $em->getRepository('App\Entity\Distribution')->getDistributionsForContract($id_contract);
            $nb_per_month = Utils::getNbPerMonth($dates); 
        }
        elseif ($type == "ventilation") {
            $report = $em->getRepository('App\Entity\Contract')->getVentilation($id_contract, $id_farm);
            $dates = $em->getRepository('App\Entity\Distribution')->getDistributionsForContract($id_contract);
            $nb_per_month = Utils::getNbPerMonth($dates);  
        }
        elseif ($type == "payment") {
            $report = $em->getRepository('App\Entity\Contract')->getPaymentByMonth($id_contract, $id_farm);
            $dates = $em->getRepository('App\Entity\Distribution')->getMonthsForContract($id_contract);            
        }
        return $this->render('Contract/report.html.twig', array(
            'contract' => $contract,
            'report' => $report,
            'payment' => $payment,
            'type' => $type,
            'farms' => $farms,
            'farm' => $farm,
            'user' => $user,
            'contract_farms' => $contract_farms,
            'dates' => $dates,
            'nb_per_month' => $nb_per_month
          ));
    }
    
    public function reportRedirect($id_payment) {
       $this->denyAccessUnlessGranted('ROLE_REFERENT');
       $em = $this->getDoctrine()->getManager();
       $payment = $em->getRepository('App\Entity\Payment')->find($id_payment);
       $id_contract = $payment->getFkContract()->getIdContract();
       return $this->redirect($this->generateUrl('contract_report', 
               array('id_contract' => $id_contract)));
    }
    
}
