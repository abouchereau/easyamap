<?php

namespace App\Controller;

use App\Entity\User;
use App\Util\Amap;
use Symfony\Component\HttpFoundation\Request;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
/**
 * Purchase controller.
 *
 */
class PurchaseController extends AmapBaseController
{
  public function index($isArchive = false)
  {
    $this->denyAccessUnlessGranted('ROLE_ADHERENT');    
    
    $em = $this->getDoctrine()->getManager();
    $user = $this->get('security.token_storage')->getToken()->getUser(); 
    $contracts = $em->getRepository('App\Entity\Contract')->findAllOrderByPeriodStart();
    $filled = $em->getRepository('App\Entity\Contract')->getFilledContracts($user);
          return $this->render('Purchase/index.html.twig', array(
            'contracts' => $contracts,
            'filled' => $filled,
            'isArchive' => $isArchive
        ));
  } 
  
  public function view($id_contract, $id_user=null) {       
    $this->denyAccessUnlessGranted('ROLE_ADHERENT');
    $em = $this->getDoctrine()->getManager();
    $user_list = null;
    $user = null;
    $current_user = $this->get('security.token_storage')->getToken()->getUser(); 
    $contract = $em->getRepository('App\Entity\Contract')->find($id_contract);   
    
     if ($em->getRepository('App\Entity\Contract')->hasEquitableAndRatio($contract)) {
        $this->get('session')->getFlashBag()->add('error', "Un problème a été détecté : il n'est pas possible d'avoir l'option \"Lissage de paiements\" avec des produits ayant un prix au poids.");
    }
    
    //on délocke les anciens isoloir
    $em->getRepository('App\Entity\Booth')->unlockOld();
    //on regarde si les contrat est locké
    $is_locked = $em->getRepository('App\Entity\Booth')->isLockedContract($id_contract, ($id_user != null?$id_user:$current_user->getIdUser()), $current_user);
    if ($is_locked) {
        return $this->render('Purchase/view_locked.html.twig', array('contract' => $contract));
    }
    //on locke le contract courant (id_contract/id_user)
    $em->getRepository('App\Entity\Booth')->lockContract($id_contract, ($id_user != null?$id_user:$current_user->getIdUser()), $current_user);
        
    
    
    if ($id_user != null) {//vue referent
        $referent = $current_user; 
        if (!$referent->isReferent() && !$referent->getIsAdmin()) {
            throw new AccessDeniedException();
        }
        $user_list = $em->getRepository('App\Entity\User')->findAllOrderByLastname();
        if ($id_user == 0) {
            return $this->render('Purchase/view_empty.html.twig', array(
                'user_list' => $user_list,
                'contract'  => $contract));
        } else {
            $user = $em->getRepository('App\Entity\User')->find($id_user);
        }
        
    }
    else {//vue normale : user = utilisateur courant
        $user = $this->get('security.token_storage')->getToken()->getUser(); 
    }


    //on récupère l'ensemble des distributions our les n mois à venir
    $distributions = $em->getRepository('App\Entity\Contract')->getDistributions($id_contract);
    
    if ($id_user != null) {//vue référent
        $products = $em->getRepository('App\Entity\Product')->findForContract($contract, $referent);
    }
    else {
        $products = $em->getRepository('App\Entity\Product')->findForContract($contract);
    }
    
    //die(count($products).' products');
    $nb_per_month = Utils::getNbPerMonth($distributions);  
    $nb_per_farm = $this->getNbPerFarm($products);
    
    $payments = $em->getRepository('App\Entity\Payment')->findForUserContract($contract, $user);
         //on récupère les produits déjà disponibles dans la période, en fonction du référent / ou admin
    //$available = $em->getRepository('App\Entity\ProductDistribution')->findAllWhereDistributionIn(array_keys($distributions));//TODO restreindre aux produits du contrat
    $available = $em->getRepository('App\Entity\ProductDistribution')->retrieveFromContract($id_contract);
    $purchase = $em->getRepository('App\Entity\Purchase')->getPurchase(array_keys($distributions), $user->getIdUser(),$contract);
    $remaining = $em->getRepository('App\Entity\ProductDistribution')->getRemaining($id_contract);
    $commandesExistantes = $em->getRepository('App\Entity\Contract')->getCommandesExistantes($id_contract,$user->getIdUser());
    
    //on retrouve l'onglet courant
    $current_farm = $this->getCurrentFarm();
    
    //comptage des produits depuis une date
    $purchaseSince = [];
    if ($contract->getCountPurchaseSince() != null) {
        $purchaseSince = $em->getRepository('App\Entity\Purchase')->getPurchaseCountSince($contract->getCountPurchaseSince(), $products, $user);
    }
  
    return $this->render('Purchase/view.html.twig', array(
          'contract'      => $contract,
          'distributions' => $distributions,
          'nb_per_month'  => $nb_per_month,
          'products'      => $products,
          'nb_per_farm'   => $nb_per_farm,
          'available'     => $available,
          'purchase'      => $purchase,
          'payments'      => $payments,
          'remaining'     => $remaining,
          'current_farm'  => $current_farm,
          'user_list'     => $user_list,
          'user'          => $user,
          'purchaseSince' => $purchaseSince,
          'commandesExistantes' => $commandesExistantes
        ));
  }
  
  private function getCurrentFarm() {
    $session = new Session();
    $current_farm = null;
    if ($session->has('current_farm')) {
        $current_farm = $session->get('current_farm');
        $session->remove('current_farm');
    }
    return $current_farm;
  }
  
  public function getProductsNextDistribution($date = null, $nb = 4)
  {
      $this->denyAccessUnlessGranted('ROLE_ADHERENT');
      
      $em = $this->getDoctrine()->getManager();
      
      if ($date === null || !preg_match("/^\d{4}\-\d{2}-\d{2}$/",$date))
      {
          $date = $em->getRepository('App\Entity\Distribution')->findNextDate();
      }
      $nb = $this->checkNbDistri($nb);
      $dates = $em->getRepository('App\Entity\Distribution')->findNDateFrom($date, $nb);
      $user = $this->get('security.token_storage')->getToken()->getUser();
      $list = $em->getRepository('App\Entity\Purchase')->getProductsToRecover($dates, $user->getIdUser());
      $participation = $em->getRepository('App\Entity\Participation')->getTasks($dates, $user->getIdUser());

      foreach($dates as $key => $date) {
        $dates[$key] = \DateTime::createFromFormat('Y-m-d', $date);
      }
      $direction = "H";
      $session = new Session();
      if (isset($_GET['direction'])) {
          $direction = $_GET['direction'];
          $session->set('direction',$direction);
      }
      elseif($session->has('direction')) {          
          $direction = $session->get('direction');
      }
      return $this->render('Purchase/distributionSummary.html.twig', array(
            'list' => $list,
            'group_by' => 'adhérent',
            'dates' => $dates,
            'nb' => $nb,
            'urlTemplate' => 'produits_a_recuperer/%DATE%/%NB%',
            'direction' => $direction,
            'participation' => $participation
        ));
  }
  
  public function getDeliveryNextDistribution($date = null, $nb = 4, $role=null)
  {
      $em = $this->getDoctrine()->getManager();
      if ($role != null) {
          $session = new Session();
          $session->set('role', 'ROLE_' . strtoupper($role));
      }
      if ($date === null || !preg_match("/^\d{4}\-\d{2}-\d{2}$/",$date))
      {
          $date = $em->getRepository('App\Entity\Distribution')->findNextDate();
      }
      $nb = $this->checkNbDistri($nb);
      $dates = $em->getRepository('App\Entity\Distribution')->findNDateFrom($date, $nb);
      $user = $this->get('security.token_storage')->getToken()->getUser();
      $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);
      $list = $em->getRepository('App\Entity\Purchase')->getProductsToShip($dates, $farms);

      foreach($dates as $key => $date)
      {
        $dates[$key] = \DateTime::createFromFormat('Y-m-d', $date);
      }
      $direction = "H";
      $session = new Session();
      if (isset($_GET['direction'])) {
          $direction = $_GET['direction'];
          $session->set('direction',$direction);
      }
      elseif($session->has('direction')) {          
          $direction = $session->get('direction');
      }
      return $this->render('Purchase/distributionSummary.html.twig', array(
            'list' => $list,
            'group_by' => 'farm',
            'dates' => $dates,
            'nb' => $nb,
            'urlTemplate' => 'produits_a_livrer/%DATE%/%NB%',
            'direction' => $direction
        ));
  }

    public function getDeliveryNextDistributionMultiAmap($dateDebut = null, $dateFin=null, $role=null)
    {
        $this->denyAccessUnlessGranted(['ROLE_FARMER','ROLE_ADHERENT']);
        if ($role != null) {
            $session = new Session();
            $session->set('role', 'ROLE_' . strtoupper($role));
        }
        if ($dateDebut == null || !preg_match("/^\d{4}\-\d{2}-\d{2}$/", $dateDebut)) {
            $dateDebut = new \DateTime();
        }
        else {
            $dateDebut = \DateTime::createFromFormat('Y-m-d', $dateDebut);
        }

        if ($dateFin == null || !preg_match("/^\d{4}\-\d{2}-\d{2}$/", $dateFin)) {
            $dateFin = clone $dateDebut;
            $interval = new \DateInterval('P7D');
            $dateFin->add($interval);
        }
        else {
            $dateFin = \DateTime::createFromFormat('Y-m-d', $dateFin);
        }

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);
        $farmsMulti = $em->getRepository('App\Entity\Farm')->getFarmsMulti($farms, $em->getConnection()->getDatabase());
        $list = $em->getRepository('App\Entity\Purchase')->getProductsToShipMulti($dateDebut, $dateFin, $farmsMulti);
        $dates = $this->retrieveDatesFromList($list);
        return $this->render('Purchase/distributionSummaryMulti.html.twig', array(
            'list' => $list,
            'group_by' => 'farm',
            'dates' => $dates,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'urlTemplate' => 'produits_a_livrer_multiamap/%DATE_DEBUT%/%DATE_FIN%',
            'direction' => "H"
        ));
    }

    public function getDeliveryNextDistributionTotal($date= null) {
        if (!Amap::isEasyamapMainServer()) {
            throw $this->createAccessDeniedException('Accès refusé');
        }
        $this->denyAccessUnlessGranted(['ROLE_FARMER','ROLE_ADHERENT']);

        if ($date == null) {
            $date = new \DateTime();
        }
        else {
            $date = \DateTime::createFromFormat('Y-m-d', $date);
        }
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);
        $farmsMulti = $em->getRepository('App\Entity\Farm')->getFarmsMulti($farms, $em->getConnection()->getDatabase());
        $productsMulti = $em->getRepository('App\Entity\Product')->getProductsMulti($farmsMulti);
    }

    private function retrieveDatesFromList($list) {
        $datesRet = [];
        foreach($list as $entity => $dates) {
            foreach($dates as $date => $items) {
                if (!in_array($date, $datesRet)) {
                    $datesRet[] = $date;
                }
            }
        }
        sort($datesRet);
        return $datesRet;
    }
  
  //$farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);
  /*public function getProductsNextDistribution(Request $request)
  {
      $date = date('Y-m-d');
      if ($request->query->get('date') != null && preg_match("/^\d{4}\-\d{2}-\d{2}$/", $request->query->get('date')))
        $date = $request->query->get('date');
      $limit = 5;
      if ($request->query->get('limit') != null && preg_match("/^\d{1,2}$/", $request->query->get('limit')))
        $limit = $request->query->get('limit');
      $user = $this->get('security.token_storage')->getToken()->getUser();

      $em = $this->getDoctrine()->getManager();
      $list = $em->getRepository('App\Entity\Purchase')->getProductsNextDistributionByUser($user, $date, $limit);
      return $this->render('Purchase/nextDistribution.html.twig', array(
            'list' => $list,
            'limit' => $limit,
            'date' => $date
        ));
  }*/
  
  public function save(Request $request)
  {
      $this->denyAccessUnlessGranted('ROLE_ADHERENT');
  //  die(ini_get('max_input_vars'));
    $em = $this->getDoctrine()->getManager();
    $tab = json_decode($request->get('json'),1);
    
    //on enregistre l'onglet courant (si il y a des onglets)
    $current_farm = $request->get('current_farm');
    if ($current_farm != null && $current_farm != 0) {
        $session = new Session();
        $session->set('current_farm',$current_farm);
    }
    
    //récupérer l'id_user s'il existe
    //le référent est l'utilisateur courant
    $id_user = $request->get('id_user');
    if ($id_user != null) {        
        $user = $em->getRepository('App\Entity\User')->find($id_user);
        $referent = $this->get('security.token_storage')->getToken()->getUser();
    }
    else {
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $referent = null;
    }
    
    $id_contract = $request->get('id_contract');
    
    $em->getConnection()->beginTransaction();    
    
    $contract = $em->getRepository('App\Entity\Contract')->find($id_contract);
    
    
    //on vide les anciennes commandes    
    $nb = $em->getRepository('App\Entity\Purchase')->emptyContract($id_contract, $user->getIdUser(), $referent);
    if ($nb === false)
      return $this->rollback($id_contract);
    
    
    //on ajoute les nouvelles    
    $ids_purchase = $em->getRepository('App\Entity\Purchase')->add($user, $tab, $contract);
    if ($ids_purchase === false)
      return $this->rollback($id_contract);
    
 
    if ($em->getRepository('App\Entity\Payment')->hasOverage())
    {
        $product_distributions = $em->getRepository('App\Entity\ProductDistribution')->retrieveFromContract($id_contract);
        $overages = $em->getRepository('App\Entity\Payment')->getOverages($product_distributions);
        if (count($overages) > 0) {
            $msg = "<p>Votre commande n'a pas été enregistrée car elle provoque le dépassement de quantités limites définies par le producteur :</p><br />";
            $msg .= "<ul>";
            foreach ($overages as $overage) {
                $msg .= "<li><b>".$overage['date']." / ".$overage['label']." ".$overage['unit']." : dépassement de <u>".$overage['excedent']." unité(s)</u>.</b></li>";
            }
            $msg .= "</ul>";
            $msg .= "<br /><p>Si possible, merci de renouveler votre commande en prenant en compte ces limites.</p>";
            $this->get('session')->getFlashBag()->add('error', $msg);
            return $this->rollback($id_contract);
        }
    }
    
    //on calcule les paiements
    $v = $em->getRepository('App\Entity\Payment')->emptyPayments($user, $contract, $referent);
    if ($v === false)
      return $this->rollback($id_contract);
    
    $v = $em->getRepository('App\Entity\Payment')->compute($user, $contract, $ids_purchase);
    if ($v === false)
      return $this->rollback($id_contract);
    
    $this->get('session')->getFlashBag()->add('notice', 'Les données ont été mises à jour.');
    $em->getConnection()->commit();
    
    $params = array('id_contract' => $id_contract);
    if ($id_user!=null) {
        $params['id_user'] = $id_user;
    }
    return $this->redirect($this->generateUrl('contrat_view',$params));
  }
  
  protected function rollback($id_contract)
  {
    $em = $this->getDoctrine()->getManager();
    $em->getConnection()->rollback();
    $this->get('session')->getFlashBag()->add('error', 'Problème lors de l\'enregistrement des données');
    return $this->redirect($this->generateUrl('contrat_view',array('id_contract' => $id_contract)));
  }
  
  protected function getNbPerFarm($products)
  {
    $nb_per_farm = array();
    foreach ($products as $product)
    {
      $id_farm = $product->getFkFarm()->getIdFarm();
      if (!isset($nb_per_farm[$id_farm]))
        $nb_per_farm[$id_farm] = 0;
      $nb_per_farm[$id_farm]++;
    }
    return $nb_per_farm;
  }
  
  public function listDistributionAdherent($date = null, $nb = 4)
  {
      $em = $this->getDoctrine()->getManager();
      if ($date === null || !preg_match("/^\d{4}\-\d{2}-\d{2}$/",$date))
      {
          $date = $em->getRepository('App\Entity\Distribution')->findNextDate();
      }
      $nb = $this->checkNbDistri($nb);
      $dates = $em->getRepository('App\Entity\Distribution')->findNDateFrom($date, $nb);
      $list = $em->getRepository('App\Entity\Purchase')->getProductsToRecover($dates);      
      $participation = $em->getRepository('App\Entity\Participation')->getTasks($dates);

      foreach($dates as $key => $date)
      {
        $dates[$key] = \DateTime::createFromFormat('Y-m-d', $date);
      }       
      return $this->render('Purchase/distributionSummary.html.twig', array(
            'list' => $list,
            'group_by' => 'adhérent',
            'dates' => $dates,
            'nb' => $nb,
            'urlTemplate' => 'liste_distribution_adherent/%DATE%/%NB%',
            'direction' => 'H',
            'participation' => $participation
        ));
  }
 
  public function listDistributionFarm($date = null, $nb = 4)
  {
      $em = $this->getDoctrine()->getManager();
      if ($date === null || !preg_match("/^\d{4}\-\d{2}-\d{2}$/",$date))
      {
          $date = $em->getRepository('App\Entity\Distribution')->findNextDate();
      }
      $nb = $this->checkNbDistri($nb);
      $dates = $em->getRepository('App\Entity\Distribution')->findNDateFrom($date, $nb);
      $list = $em->getRepository('App\Entity\Purchase')->getProductsToShip($dates);

      foreach($dates as $key => $date)
      {
        $dates[$key] = \DateTime::createFromFormat('Y-m-d', $date);
      }
      return $this->render('Purchase/distributionSummary.html.twig', array(
            'list' => $list,
            'group_by' => 'farm',
            'dates' => $dates,
            'nb' => $nb,
            'urlTemplate' => 'liste_distribution_producteur/%DATE%/%NB%',
            'direction' => 'H'
        ));
  }
    
  protected function checkNbDistri($nb)
  {
      $nb = (int) $nb;
      if ($nb > 14)
          $nb = 14;
      if ($nb < 1)
          $nb = 1;
      return $nb;
  }
  
  public function rapport($role=null)
  {
      $this->denyAccessUnlessGranted([User::ROLE_FARMER, User::ROLE_REFERENT]);
        if ($role != null) {
            $session = new Session();
            $session->set('role','ROLE_'.strtoupper($role));
        }

      $id_farm = isset($_GET['id_farm']) ? $_GET['id_farm'] : null;
      $date_debut = isset($_GET['date_debut']) ? $_GET['date_debut'] : null;
      $date_fin = isset($_GET['date_fin']) ? $_GET['date_fin'] : null;
      $id_user = isset($_GET['id_user']) ? $_GET['id_user'] : null;
      $hide_empty_products = isset($_GET['hide_empty_products']);
      if ($id_user == "all") {
          $id_user = null;
      }

      $user = $this->get('security.token_storage')->getToken()->getUser();
      $em = $this->getDoctrine()->getManager();
      $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);
      if($id_farm == null) {
          $farm = $farms[0];
      }
      else {
          $farm = $em->getRepository('App\Entity\Farm')->find($id_farm);
      }
      if ($date_debut==null) {
          $date_debut = new \DateTime('first day of January ' . date('Y'));
      }
      else {
          $date_debut = \DateTime::createFromFormat('Y-m-d', $date_debut);
      }
      if ($date_fin==null) {
          $date_fin = new \DateTime('last day of December ' . date('Y'));
      }
      else {
          $date_fin = \DateTime::createFromFormat('Y-m-d', $date_fin);
      }
      $quantities = $em->getRepository('App\Entity\Purchase')->getQuantities($farm->getIdFarm(), $date_debut, $date_fin, $id_user);
      $products = $em->getRepository('App\Entity\Product')->findForFarm($farm, $hide_empty_products?$quantities['product_list']:[]);
      $user_list = $em->getRepository('App\Entity\User')->findAllOrderByLastname();

      return $this->render('Stats/rapport.html.twig', array(
            'farms' => $farms,
            'quantities' => $quantities,
            'products' => $products,
            'farm' => $farm,
            'date_debut' => $date_debut,
            'date_fin' => $date_fin,
            'id_user' => $id_user,
            'user_list' => $user_list,
            'hide_empty_products' => $hide_empty_products
        ));
  }

    public function tableauLivraisonParProduit($dateDebutStr = null)
    {
        $session = new Session();
        $session->set('role','ROLE_FARMER');
        $this->denyAccessUnlessGranted(['ROLE_FARMER','ROLE_ADHERENT']);
        $dateDebut = new \DateTime();
        if ($dateDebutStr == null || !preg_match("/^\d{4}\-\d{2}-\d{2}$/", $dateDebutStr)) {
            $dateDebut->setTimestamp(strtotime('last monday'));
        }
        else {
            //lundi précédent
            $dateDebut = \DateTime::createFromFormat('Y-m-d', $dateDebutStr);
            $delta = ($dateDebut->format('w') + 6) % 7;
            $dateDebut->sub(new \DateInterval('P'.$delta.'D'));
        }

        $dateFin = clone $dateDebut;
        $dateFin->add(new \DateInterval('P7D'));

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);
        $farmsMulti = $em->getRepository('App\Entity\Farm')->getFarmsMulti($farms, $em->getConnection()->getDatabase());
        $data = $em->getRepository('App\Entity\Purchase')->getProductsToShipMulti2($dateDebut, $dateFin, $farmsMulti);


        $dateFin->sub(new \DateInterval('P1D'));//pour l'affichage

        return $this->render('Purchase/tableauLivraisonParProduit.html.twig', array(
            'amaps' => $data['amaps'],
            'produits' => $data['produits'],
            'quantities' => $data['quantities'],
            'total' => $data['total'],
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'urlTemplate' => 'tableau_livraison_par_produit/%DATE%',
        ));
    }

}