<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckSqlModeCommand extends Command {

   
    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
                ->setName('amap:check-sqlmode')
                ->setDescription('Vérifie que le sql-mode ne contient pas ONLY_FULL_GROUP_BY')
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
        $sql = "select @@sql_mode";
        $r = $conn->query($sql);
        $mode = $r->fetch(\PDO::FETCH_COLUMN);
        
        if (strpos($mode,'ONLY_FULL_GROUP_BY') !== false) {    
            try {
                $conn->exec("SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            }
            catch(\Exception $e) {
                echo $e->getMessage();
            }
            $sql = "select @@sql_mode";
            $r = $conn->query($sql);
            $mode = $r->fetch(\PDO::FETCH_COLUMN);
            
            

            $message = (new \SwiftMessage())
                ->setSubject('easyamap : problème sql_mode')
                ->setFrom(array('ne_pas_repondre@easyamap.fr' => "easyamap"))
                ->setTo('anthonybouchereau@hotmail.com')
                ->setBody('Le mode ONLY_FULL_GROUP_BY est revenu :('.PHP_EOL.'Retablissemnt du SQL_MODE à '.$mode);
               $mailer = $container->get('mailer');
               $v = $mailer->send($message);
               if (!$v)
                 echo 'Echec de l\'envoi de l\'e-mail';

            
        }
    }
}