<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Payment;
use App\Entity\PaymentSplit;
use App\Form\PaymentType;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
/**
 * Payment controller.
 *
 */
class PaymentController extends AmapBaseController
{
    const NB_PER_PAGE = 50;
    
    public function indexAdherent(Request $request, $page=1, $contract=0, $farm=0, $received=0) {
        $curUser = $this->get('security.token_storage')->getToken()->getUser();
        $filters = ['contract'=>$contract,'farm'=>$farm,'received'=>$received];
        $em = $this->getDoctrine()->getManager();

        $payments = $em->getRepository('App\Entity\Payment')->getForAdherent($curUser, $filters, $page, self::NB_PER_PAGE);
        $contracts = $em->getRepository('App\Entity\Contract')->findAllOrderByIdDescDoctrine($curUser);
        $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($curUser);

        $pagination = [
            'page' => $page,
            'nbPages' => ceil(count($payments) / self::NB_PER_PAGE),
            'paramsRoute' => $filters
        ];
        return $this->render('Payment/list.html.twig', [
            'payments' => $payments,
            'pagination' => $pagination,
            'filters' => $filters,
            'contracts' => $contracts,
            'farms' => $farms,
            'adherents' => [],
            'isReferentPage' => false
        ]);
    }

    
    public function indexReferent(Request $request, $page=1, $contract=0, $farm=0, $received=0, $adherent=0) {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        $curUser = $this->get('security.token_storage')->getToken()->getUser();
        $filters = ['contract'=>$contract,'farm'=>$farm,'received'=>$received,'adherent'=>$adherent];
        $em = $this->getDoctrine()->getManager();
        $payments = $em->getRepository('App\Entity\Payment')->getForReferent($curUser, $filters, $page, self::NB_PER_PAGE);
        $contracts = $em->getRepository('App\Entity\Contract')->findAllOrderByIdDescDoctrine($curUser);
        $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($curUser);
        $adherents = $em->getRepository('App\Entity\User')->findBy(['isActive'=>1],['lastname'=>'ASC']);
        $pagination = [
            'page' => $page,
            'nbPages' => ceil(count($payments) / self::NB_PER_PAGE),
            'paramsRoute' => $filters
        ];
        return $this->render('Payment/list.html.twig', [
            'payments' => $payments,
            'pagination' => $pagination,
            'filters' => $filters,
            'contracts' => $contracts,
            'farms' => $farms,
            'adherents' => $adherents,
            'isReferentPage' => true
        ]);
    }

    /*
    private function getFilters(Request $request) {
        $filters = [];
        $available = ['contract','farm','received'];
        foreach($available as $param) {
            if ($request->query->get($param) != null) {
                $filters[$param] = $request->query->get($param);
            }
        }
    }*/
        
        

    /**
     * Creates a new Payment entity.
     *
     */
    public function create(Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        $entity = new Payment();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $entity->setReceivedAt(new \DateTime());
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);            
            $ps = new PaymentSplit();
            $ps->setFkPayment($entity);
            $ps->setAmount($entity->getAmount());
            $ps->setDate(new \DateTime());
            $em->persist($ps);
            $em->flush();
            $this->get('session')->getFlashBag()->add('notice', 'Le paiement a été ajouté.');
            return $this->redirect($this->generateUrl('payment_history',array('user'=>$entity->getFkUser()->getIdUser(),'farm'=>$entity->getFkFarm()->getIdFarm())));
        }        
        else
        {
          $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données '.$form->getErrors(true, false));
        }

        return $this->render('Payment/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Payment entity.
     *
     * @param Payment $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Payment $entity)
    {
        $form = $this->createForm(PaymentType::class, $entity, array(
            'action' => $this->generateUrl('payment_create'),
            'method' => 'POST',
            'user' => $this->get('security.token_storage')->getToken()->getUser()
        ));

        $form->add('submit', SubmitType::class, array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Payment entity.
     *
     */
    public function new($id_user=null, $id_farm=null)
    {        
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        $entity = new Payment();
        $em = $this->getDoctrine()->getManager();
        if ($id_user != null) {
            $user = $em->getRepository('App\Entity\User')->find($id_user);
            $entity->setFkUser($user);
        }
        if ($id_farm != null) {
            $farm = $em->getRepository('App\Entity\Farm')->find($id_farm);
            $entity->setFkFarm($farm);
        }
        $form   = $this->createCreateForm($entity);

        return $this->render('Payment/new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }


    public function stats() {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em = $this->getDoctrine()->getManager();
        $year = date('Y');  
        $stats = $em->getRepository('App\Entity\Payment')->stats($year,null,null);
        $years = $em->getRepository('App\Entity\Payment')->getAllYears();
        $users = $em->getRepository('App\Entity\User')->findAllOrderByLastname();
        $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel2();
        return $this->render('Stats/payments.html.twig', array(
            'stats' => $stats,
            'users' => $users,
            'farms' => $farms,
            'years' => $years
        ));
    }
    
    public function ajaxStats($year, $id_user, $id_farm) 
    {
        if ($id_user==0)
            $id_user = null;
        if ($id_farm==0)
            $id_farm = null;
        $em = $this->getDoctrine()->getManager();      
        $stats = $em->getRepository('App\Entity\Payment')->stats($year, $id_user, $id_farm);
        return new Response(json_encode($stats));
    }
    
    public function paymentDescription(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();    
        $descr = $request->request->get('description');
        $id_payment = $request->request->get('id_payment');
        $payment = $em->getRepository('App\Entity\Payment')->find($id_payment);
        $payment->setDescription($descr);
        $em->persist($payment);
        $em->flush();
        return new Response('ok');
    }
     
    public function paymentAmount(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();    
        $amount = Utils::numerize($request->request->get('amount'));
        $id_payment = $request->request->get('id_payment');
        $payment = $em->getRepository('App\Entity\Payment')->find($id_payment);
        $payment->setAmount($amount);
        $em->persist($payment);
        $em->flush();
        //$em->getRepository('App\Entity\Payment')->majStat($id_payment);
        return new Response('ok');
     }
     
     public function paymentReceived(Request $request) {
        $this->denyAccessUnlessGranted('ROLE_REFERENT');
        
        $em = $this->getDoctrine()->getManager();    
        $amount = Utils::numerize($request->request->get('received'));
        $id_payment = $request->request->get('id_payment');
        $split_index = (int)$request->request->get('split_index');
        $split = $request->request->get('split');
        if ($split_index>-1 && !$em->getRepository('App\Entity\Payment')->checkReceived($amount, $split) ) {
            return new Response('<h5>Echec lors de l\'enregistrement</h5><p>La répartition des montants n\'est pas égale pas au montant total.</p>');
        } else {
            $em->getConnection()->beginTransaction();
            $description = $em->getRepository('App\Entity\Payment')->manageReceived($id_payment, $amount, $split_index, $split);        
//            $v = $em->getRepository('App\Entity\Payment')->majStat($id_payment);
            if (gettype($description) == "array") {
                $em->getConnection()->commit();
            }
            else {
                $em->getConnection()->rollback();
            }

            return new Response(json_encode($description));
        } 
     }
     
    /* public function paymentHistoryFromOnePayment($id_payment) {
        $em = $this->getDoctrine()->getManager();  
        $payment = $em->getRepository('App\Entity\Payment')->find($id_payment);

         if ($payment == null)
             throw new AccessDeniedException();
        $user = $payment->getFkUser();
        $farm = $payment->getFkFarm();
        return $this->redirect($this->generateUrl('payment_history',array('id_user'=>$user->getIdUser(),'id_farm'=>$farm->getIdFarm())));
     }
     */
     public function paymentHistoryAdherent (Request $request) {
         $curUser = $this->get('security.token_storage')->getToken()->getUser();
         return $this->paymentHistory($request, $curUser);
     }
     
     public function paymentHistoryFarmer (Request $request) {
         $this->denyAccessUnlessGranted('ROLE_FARMER');
         $curUser = $this->get('security.token_storage')->getToken()->getUser();
         $farm = $curUser->getFkFarm();
         return $this->paymentHistory($request, null, $farm);
     }
     
     public function paymentHistory(Request $request, $force_user=null, $force_farm=null) {   
        $em = $this->getDoctrine()->getManager();   
        
        $role = 'referent';
        if ($force_user != null) {
            $role = 'adherent';
        }
        if ($force_farm != null) {
            $role = 'farmer';
        }
        if ($force_farm == null && $force_user==null) {
             $this->denyAccessUnlessGranted('ROLE_REFERENT');
         }
         
        $id_user = $request->query->get('user');
        $id_farm = $request->query->get('farm');
        $year    = $request->query->get('year');
        
        if (empty($year)) {
            $year = date('Y');
        }      
        $years = $em->getRepository('App\Entity\Payment')->getDistinctYears();
        
               
    
        $user = null;
        if ($force_user != null) {
            $user = $force_user;            
        }
        elseif ($id_user != null) {
            $user = $em->getRepository('App\Entity\User')->find($id_user);
        }
        
        $farm = null;
        if ($force_farm != null) {
            $farm = $force_farm;
        }
        elseif ($id_farm != null) {
            $farm = $em->getRepository('App\Entity\Farm')->find($id_farm);
        }

         $curUser = $this->get('security.token_storage')->getToken()->getUser();
         $farms_id = array();
         $users = array();
         if ($force_user != null) {
             $farms = $em->getRepository('App\Entity\Farm')->findAll(array(),array('isActive'=>'DESC','sequence'=>'ASC'));
         }
         else{
           $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($curUser);
           //$farm = $farms[0];
           $users = $em->getRepository('App\Entity\User')->findAllOrderByLastname();
         }
          if ($curUser->isReferent()) {
            foreach($farms as $each) {
                $farms_id[] = $each->getIdFarm();
            }
         }
         

         if ($user != null && $user->getIdUser() == $curUser->getIdUser()) {//adehrent        
         } 
         elseif ($curUser->getIsAdmin()) {//admin
         } 
         elseif (in_array($farm->getIdFarm(), $farms_id)) { //referent
         } 
         else {
            throw new AccessDeniedException();
         }
         
         $can_add = ($farm != null && ($curUser->getIsAdmin() || in_array($farm->getIdFarm(), $farms_id)));
         
         $payments = array();
         if ($user != null && $farm != null) {
            $payments = $em->getRepository('App\Entity\Payment')->history($user, $farm, $year);
         }
         
         return $this->render('Payment/history.html.twig', array(
            'farms' => $farms,
            'users' => $users,
            'user' => $user,
            'farm' => $farm,
            'payments' => $payments,
            'can_add' => $can_add,
            'year' => $year,
            'years' => $years,
             'role' => $role

        ));
     }
     
     public function statsActivite() {
         
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $em = $this->getDoctrine()->getManager(); 
        $months = $em->getRepository('App\Entity\Distribution')->findAllForStat();
        $userMonth = $em->getRepository('App\Entity\Purchase')->userPurchaseMonth();
        return $this->render('Stats/activite.html.twig', array(
            'months' => $months,
            'userMonth' => $userMonth
        ));
     }
}
