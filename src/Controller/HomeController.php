<?php

namespace App\Controller;

use App\Util\Amap;
use Symfony\Component\HttpFoundation\Response;
use App\Util\Cache;
use App\Util\GitUtils;
use App\Entity\User;

class HomeController extends AmapBaseController
{
    public function index()
    {
        $user = $this->getUser();      //null si non authentifié  
        $menu = $this->getMenu($user);

        return $this->render('Home/index.html.twig',  array(
          'menu' => $menu
          ));
    }
    
    public function maintenance() {
        return $this->render('Home/maintenance.html.twig');
    }
    
    protected function getMenu($user)
    {
        $menu = array();
        if ($user->hasRole(User::ROLE_FARMER)) {
            $menu['farmer'] = $this->getMenuFarmer($user);
        }
        if ($user->hasRole(User::ROLE_ADHERENT)) {
            $menu['adherent'] = $this->getMenuAdherent($user);
        }
        if ($user->hasRole(User::ROLE_REFERENT)) {
            $menu['referent'] = $this->getMenuReferent($user);
        }
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $menu['admin'] = $this->getMenuAdmin($user);
        }
        return $menu;
    }
    
    protected function getMenuFarmer($user) {
        $list = array();
        if (true||Amap::isEasyamapMainServer()) {
            $list[] = array(
                $this->generateUrl('products_to_ship_multi',['role'=>'farmer','dateDebut'=>' ', 'dateFin' => ' ']),
                'circle-arrow-down',
                'Produits à livrer',
                'Liste des produits à livrer aux prochaines distributions.'
            );
        } else {
            $list[] = array(
                $this->generateUrl('products_to_ship',['role'=>'farmer','date'=>'0', 'nb'=>4]),
                'circle-arrow-down',
                'Produits à livrer',
                'Liste des produits à livrer aux prochaines distributions.'
            );
        }

        $list[] = array(
            $this->generateUrl('contract_farmer'),
            'blackboard',
            'Contrats',
            'Comptes-rendus des contrats');
        $list[] = array(
             $this->generateUrl('rapport',['role'=>'farmer']),
             'stats',
             'Rapports',
             'Statistiques par produit'
             );
        /*$list[] = array(
             $this->generateUrl('payment_history_farmer'),
             'plus',
             'Somme des paiements',
             'Somme des paiements par annnée et par adhérent.'
             );*/
        return $list;
    }
    
    protected function getMenuAdherent($user)
    {
        $em = $this->getDoctrine()->getManager();        
        $contracts = $em->getRepository('App\Entity\Contract')->getActiveContracts();
        $nb_not_received = $em->getRepository('App\Entity\Payment')->getNbNotReceived($user);
        $payment_descr = '('.$nb_not_received.' non reçu'.($nb_not_received>1?'s':'').')';

        $list = array();
        $list[] = array(
                $this->generateUrl('products_next_distribution'),
                'shopping-cart',
                'Prochaine distribution',
                'Liste des produits à récupérer aux prochaines distributions.'
                );
        $list[] = array(
                $this->generateUrl('contrat_list'),
                'list-alt',
                'Contrats ('.count($contracts).' en cours)',
                ''/*implode('<br />', $contracts)*/
                );
        $list[] = array(
                $this->generateUrl('paiements_adherent',['received'=>'2']) ,
                'euro',
                'Paiements '.$payment_descr,
                'Liste des paiements effectués et à effectuer'
                );
        if ($em->getRepository('App\Entity\Setting')->get('registerDistribution', $_SERVER['APP_ENV'])) {
            $list[] = array(
                    $this->generateUrl('participation') ,
                    'calendar',
                    'Inscription distributions',
                    'S\'inscrire à une distribution'
                    );
        }
        if ($em->getRepository('App\Entity\Setting')->get('useReport', $_SERVER['APP_ENV'])) {
            $list[] = array(
                    $this->generateUrl('rapport_distribution') ,
                    'blackboard',
                    'Rapports de distribution',
                    'Contrôles de livraison, bilan distribution'
                    );
        }
     /*   $list[] = array(
             $this->generateUrl('payment_history_adherent'),
             'plus',
             'Somme des paiements',
             'Somme des paiements par annnée et par producteur.'
             );*/
        return $list;
    }
    
    protected function getMenuReferent($user)
    {
        $list = array();
        if (!$user->hasRole(User::ROLE_ADMIN))
        {
        $list[] = array(
            $this->generateUrl('products_to_ship',['role'=>'referent','date'=>'0', 'nb'=>4]),
            'circle-arrow-down',
            'Produits à livrer',
            'Liste des produits à livrer aux prochaines distributions.'
            );
        }
        $list[] = array(
            $this->generateUrl('paiements_referent'),
            'euro',
            'Réception des paiements',
            'Noter les paiements reçus et attendus.'
            );
        $list[] = array(
            $this->generateUrl('product_distribution'),
            'check',
            'Disponibilité des produits',
            'Définir les dates de disponibilité des produits. Définir des quantités limites.'
            );
        $list[] = array(
            $this->generateUrl('contract_index'),
            'list-alt',
            'Contrats',
            'Créer un contrat. Activer / clôturer / modifier les commandes. Voir les comptes-rendus. ')
             ;
         $list[] = array(
             $this->generateUrl('product'),
             'apple',
             'Produits',
             'Ajouter / supprimer / modifier un produit.'
             );
         if (!$user->hasRole(User::ROLE_ADMIN))
         {
             $list[] = array(
                $this->generateUrl('farm'),
                'grain',
                'Producteurs',
                'Modifier les informations d\'un producteur.'
                 );
         }
         $list[] = array(
             $this->generateUrl('tableau_livraisons'),
             'inbox',
             'Tableau des livraisons',
             'Tableau des livraisons par distribution'
             );
         $list[] = array(
             $this->generateUrl('rapport',['role'=>'referent']),
             'stats',
             'Rapports',
             'Statistiques par produit'
             );
         $list[] = array(
             $this->generateUrl('shift'),
             'transfer',
             'Reports de livraison',
             'Définir les reports de produits'
             );
         
       /*  $list[] = array(
             $this->generateUrl('payment_history'),
             'plus',
             'Somme des paiements',
             'Somme des paiements par annnée.'
             );*/
         return $list;
    }
    
    protected function getMenuAdmin($user)
    {
        $em = $this->getDoctrine()->getManager();      
        $list = array();
        $list[] = array(
            $this->generateUrl('user'),
            'user',
            'Adhérents',
            'Ajouter / modifier / supprimer un adhérent.'
            );
        $list[] = array(
            $this->generateUrl('farm'),
            'grain',
            'Producteurs',
            'Ajouter / modifier / supprimer un producteur. Modifier le(s) référent(s).'
            );
        $list[] = array(
            $this->generateUrl('list_distribution_adherent'),
            'list',
            'Liste par adhérent',
            'Liste distribution par adhérent.'
            );
        $list[] = array(
            $this->generateUrl('list_distribution_farm'),
            'list',
            'Liste par producteur',
            'Liste distribution par producteur.'
            );
        if ($em->getRepository('App\Entity\Setting')->get('registerDistribution', $_SERVER['APP_ENV'])) {
            $list[] = array(
                   $this->generateUrl('participation_admin') ,
                   'calendar',
                   'Inscription distributions',
                   'Gérer les inscriptions aux distributions'
                   );
        }
        $list[] = array(
            $this->generateUrl('stats_finances'),
            'stats',
            'Statistiques',
            'Statistiques finances et activité'
            );
        $list[] = array(
            $this->generateUrl('setting_edit'),
            'wrench',
            'Paramètres',
            'Définir les paramètres de l\'application et les dates de distribution.'
            );
        if (Amap::isEasyamapMainServer()) {
            $list[] = array(
                $this->generateUrl('donnees'),
                'hdd',
                'Données de l\'application',
                'Visualiser et récupérer les données de l\'application.'
            );
        }
        return $list;
    }
    
    public function test() {
        return $this->render('Home/test.html.twig');
    }
    
    public function clearCacheAction () {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $env = $_SERVER['APP_ENV'];
        Cache::clear($env);
        return new Response('');
    }
    
    public function clearCacheAllAction () {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');        
        Cache::clearAllEnv();
        return new Response('');
    }
    
   /* public function getVersion() {
        return new Response(GitUtils::getLastTag());
    }*/
    
    public function showHistory() {
        return $this->render('Home/show_history.html.twig');
    }
    
    public function footer() {
        $em = $this->getDoctrine()->getManager();     
        $setting = $em->getRepository('App\Entity\Setting')->getFromCache($_SERVER['APP_ENV']);
        return $this->render('Partials/_footer.html.twig', array(
            'setting' => $setting
                ));;
    }
    
    public function town() {
        $em = $this->getDoctrine()->getManager();  
        $setting = $em->getRepository("App\Entity\Setting")->getFromCache($_SERVER['APP_ENV']);      
        return $this->render('Partials/_town.html.twig', array(
            'setting' => $setting
                ));
    }
    
    public function environment() {
        return new Response($_SERVER['APP_ENV']);
    }

    public function donnees() {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $em = $this->getDoctrine()->getManager();
        $backups = $em->getRepository("App\Entity\Setting")->getBackups($em->getConnection()->getDatabase());
        return $this->render('Home/donnees.html.twig', array(
            'backups' => $backups
        ));
    }

    public function downloadBackup($file) {
        if (strpos($file,'/')!==false) {
            return;
        }

        $path = __DIR__."/../../../../backup/".$file;
        if (!is_file($path)) {
            return;
        }

        $file_name = basename($path);

        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=$file_name");
        header("Content-Length: " . filesize($path));

        readfile($path);
        exit;
    }
}
