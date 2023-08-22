<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckViewsCommand extends Command {

   
    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
                ->setName('amap:check-views')
                ->setDescription('Vérifie que les vues ne sont pas gelée')
        ;
    }

    /**
     * Exécution de la commande
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)  {
        $container = $this->getApplication()->getKernel()->getContainer();
        $em = $container->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $db = $conn->getDatabase();
        $sql = "SELECT table_name, VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS where table_schema='".$db."'";
        $r = $conn->query($sql);
        $tab = $r->fetchAll(\PDO::FETCH_KEY_PAIR);
        
        $mail = '';
        
        foreach($tab as $view_name => $definition) {
            $sql1 = "select count(*) from ".$view_name;
            $r = $conn->query($sql1);
            $nb1 = $r->fetch(\PDO::FETCH_COLUMN);
            
            $sql2 = "select count(*) from (".$definition.") t";
            $r = $conn->query($sql2);
            $nb2 = $r->fetch(\PDO::FETCH_COLUMN);
            
            if ($nb1 != $nb2) {                
                $mail .= $view_name." => ".$nb1." / ".$nb2.PHP_EOL;                
            }            
        }
        
        if ($mail != '') {
            $message = (new \SwiftMessage())
                ->setSubject('easyamap : problème vue')
                ->setFrom(array('ne_pas_repondre@easyamap.fr' => "easyamap"))
                ->setTo('anthonybouchereau@hotmail.com')
                ->setBody($mail);
               $mailer = $container->get('mailer');
               $v = $mailer->send($message);
               if (!$v)
                 echo 'Echec de l\'envoi de l\'e-mail';
        }
        
    }
}