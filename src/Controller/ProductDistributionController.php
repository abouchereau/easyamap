<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Util\Utils;

class ProductDistributionController extends AmapBaseController
{
  const NB_PER_PAGE = 20;
    
  public function index($page = 1)
  {
    $this->denyAccessUnlessGranted('ROLE_REFERENT');
    
    $user = $this->get('security.token_storage')->getToken()->getUser();
    $em = $this->getDoctrine()->getManager();
    
    $first = ($page - 1) * self::NB_PER_PAGE;   
    
    //on récupère l'ensemble des distributions our les n mois à venir
    $distributions = $em->getRepository('App\Entity\Distribution')->findAllOffset($first, self::NB_PER_PAGE);
    $nb_per_month = Utils::getNbPerMonth($distributions);  

    //on récupère la liste des produits en fonction du référent / ou admin, groupés par producteur   
    if ($user->getIsAdmin())
      $products = $em->getRepository('App\Entity\Product')->findAllOrderByFarm(true);
    elseif ($user->isReferent())
      $products = $em->getRepository('App\Entity\Product')->findAllForReferent($user);
    $nb_per_farm = $this->getNbPerFarm($products);
    
    //on récupère les produits déjà disponibles dans la période, en fonction du référent / ou admin
    $product_distribution = $em->getRepository('App\Entity\ProductDistribution')->findAllWhereDistributionIn(array_keys($distributions), ($user->getIsAdmin()?null:$user));
    $product_distribution_shift = $em->getRepository('App\Entity\ProductDistribution')->findAllShiftWhereDistributionIn(array_keys($distributions), ($user->getIsAdmin()?null:$user));
    
    return $this->render('ProductDistribution/index.html.twig', array(
          'distributions' => $distributions,
          'nb_per_month' => $nb_per_month,
          'products' => $products,
          'product_distribution' => $product_distribution,
          'product_distribution_shift' => $product_distribution_shift,
          'nb_per_farm' => $nb_per_farm,
          'page' => $page
    ));
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
  
  public function save(Request $request)
  {
    $this->denyAccessUnlessGranted('ROLE_REFERENT');
    
    $existing = json_decode($request->request->get('existing'),1);
    $new_ones = json_decode($request->request->get('new_ones'),1);
    $page = $request->request->get('page');
    $em = $this->getDoctrine()->getManager();
    $ret = $em->getRepository('App\Entity\ProductDistribution')->save($existing, $new_ones);
    $msg = ($ret[0]>0?$ret[0].' ajout'.($ret[0]>1?'s':'').' effectué'.($ret[0]>1?'s':''):'');
    if ($ret[0]>0 && $ret[1]>0)
      $msg .= ', ';
    $msg .= ($ret[1]>0?$ret[1].' suppression'.($ret[1]>1?'s':'').' effectuée'.($ret[1]>1?'s':''):'');
    $this->get('session')->getFlashBag()->add('notice', $msg);
    return $this->redirect($this->generateUrl('product_distribution',array('page' => $page)));
  }
  
  /*public function getProductsNextDistribution(Request $request)
  {
      $this->denyAccessUnlessGranted('ROLE_REFERENT');
      
      $user = $this->get('security.token_storage')->getToken()->getUser();
      $em = $this->getDoctrine()->getManager(); 
      $farms = $em->getRepository('App\Entity\Farm')->findAllOrderByLabel($user);

      $date = date('Y-m-d');
      if ($request->query->get('date') != null && preg_match("/^\d{4}\-\d{2}-\d{2}$/", $request->query->get('date')))
        $date = $request->query->get('date');
      $limit = 1;
      if ($request->query->get('limit') != null && preg_match("/^\d{1,2}$/", $request->query->get('limit')))
        $limit = $request->query->get('limit');
      if ($request->query->get('id_farm') != null && preg_match("/^\d+$/", $request->query->get('id_farm')))
      {
        $id_farm = $request->query->get('id_farm');
        $farm = $em->getRepository('App\Entity\Farm')->find($id_farm);
      }
      else
      {
        $farm = $farms[0];
      }
            
      $list = $em->getRepository('App\Entity\Purchase')->getProductsNextDistributionByFarm($farm, $date, $limit);
      return $this->render('Purchase/nextDistribution.html.twig', array(
            'list' => $list,
            'limit' => $limit,
            'date' => $date,
            'farms' => $farms,
            'selected_farm' => $farm
        ));
  }*/
  
  public function saveProdis(Request $request) {
      $this->denyAccessUnlessGranted('ROLE_REFERENT');
      
      $id_product_distribution = $request->request->get('id_product_distribution');
      $price = (float)str_replace(',','.',$request->request->get('price'));
      $max_quantity = $request->request->get('max_quantity');
      if ($max_quantity==0 || $max_quantity=='0' || $max_quantity=='null')
          $max_quantity = null;
      $max_per_user = $request->request->get('max_per_user');
      if ($max_per_user==0 || $max_per_user=='0' || $max_per_user=='null')
          $max_per_user = null;
      
      $em = $this->getDoctrine()->getManager();
      $pd = $em->getRepository('App\Entity\ProductDistribution')->find($id_product_distribution);      
      $pd->setMaxQuantity($max_quantity);
      $pd->setMaxPerUser($max_per_user);
      $pd->setPrice($price);
      $em->persist($pd);
      $em->flush();
      $this->get('session')->getFlashBag()->add('notice', "Modifications enregistrées");
      return $this->redirect($this->generateUrl('product_distribution'));
  }
  
  public function tableauLivraisons($date = null) {
      if ($date != null) {
          $mois = 1*substr($date,0,2);
          $annee = 1*substr($date,3,4);
      }
      else {
          $mois = 1*date('n');
          $annee = 1*date('Y');          
      }
      $em = $this->getDoctrine()->getManager();
      $farms = $em->getRepository('App\Entity\Farm')->findBy(array('isActive'=>1),array('sequence'=>'ASC'));
      $distributions = $em->getRepository('App\Entity\Distribution')->getInMonth($mois,$annee);
      $livraisons = $em->getRepository('App\Entity\ProductDistribution')->getLivraisonsInMonth($mois,$annee);
      $totaux = array();    
      foreach ($livraisons as $key => $val) {
          $tmp = explode('_',$key);
          $date = $tmp[0];
          if(!isset($totaux[$date])) {
              $totaux[$date] = 0;
          }
          $totaux[$date]++;
      }
      return $this->render('ProductDistribution/livraisons.html.twig', array(
          'mois' => $mois,
          'annee' => $annee,
          'farms' => $farms,
          'distributions' => $distributions,
          'livraisons' => $livraisons,
          'totaux'    => $totaux
    ));
  }
  
  public function shiftProduct($page = 1) {
    $this->denyAccessUnlessGranted('ROLE_REFERENT');
    
    $user = $this->get('security.token_storage')->getToken()->getUser();
    $em = $this->getDoctrine()->getManager();
    
    $first = ($page - 1) * self::NB_PER_PAGE;   
    
    //on récupère l'ensemble des distributions our les n mois à venir
    $distributions = $em->getRepository('App\Entity\Distribution')->findAllOffset($first, self::NB_PER_PAGE);
    $distribution_select = $em->getRepository('App\Entity\Distribution')->findAllOffset($first - self::NB_PER_PAGE, self::NB_PER_PAGE*3);
    $nb_per_month = Utils::getNbPerMonth($distributions);  

    //on récupère la liste des produits en fonction du référent / ou admin, groupés par producteur   
    if ($user->getIsAdmin())
      $products = $em->getRepository('App\Entity\Product')->findAllOrderByFarm(true);
    elseif ($user->isReferent())
      $products = $em->getRepository('App\Entity\Product')->findAllForReferent($user);
    $nb_per_farm = $this->getNbPerFarm($products);
    
    //on récupère les produits déjà disponibles dans la période, en fonction du référent / ou admin
    $product_distribution = $em->getRepository('App\Entity\ProductDistribution')->findAllWhereDistributionIn(array_keys($distributions), ($user->getIsAdmin()?null:$user));
    $product_distribution_shift = $em->getRepository('App\Entity\ProductDistribution')->findAllShiftWhereDistributionIn(array_keys($distributions), ($user->getIsAdmin()?null:$user));
    
    return $this->render('ProductDistribution/shift.html.twig', [
          'distributions' => $distributions,
          'distribution_select' => $distribution_select,
          'nb_per_month' => $nb_per_month,
          'products' => $products,
          'product_distribution' => $product_distribution,
          'product_distribution_shift' => $product_distribution_shift,
          'nb_per_farm' => $nb_per_farm,
          'page' => $page
    ]);
  }
  
  public function shiftSave(Request $request) {      
      $this->denyAccessUnlessGranted('ROLE_REFERENT');
      $em = $this->getDoctrine()->getManager();
      $new_id_distribution = $request->get("new_id_distribution")*1;
      $page = $request->get("page")*1;
      $type_report = $request->get("type_report")*1;
      $selected = explode(",",$request->get("selected"));
      try {
        $em->getRepository('App\Entity\ProductDistribution')->report($selected,$new_id_distribution,$type_report);
        $this->get('session')->getFlashBag()->add('notice', "Modifications enregistrées");
      }
      catch(\Exception $e) {
          $this->get('session')->getFlashBag()->add('error', "Problème lors de l'enregistrement : ".$e->getMessage());
      }
      
      return $this->redirect($this->generateUrl('shift',array('page' => $page)));
  }
}