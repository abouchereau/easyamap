<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ComputePaymentsCommand extends Command {

   
    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
                ->setName('amap:compute-payments')
                ->setDescription('Insère les paiements suite à la procédure SQL add_random_purchase')
                ->addArgument('id_contract', InputArgument::REQUIRED, 'id contract')
        ;
    }

    /**
     * Exécution de la commande
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) {

        $container = $this->getApplication()->getKernel()->getContainer();
        $em = $container->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $id_contract = $input->getArgument('id_contract');
        $contract = $em->getRepository('App\Entity\Contract')->find($id_contract);
        $sql = "SELECT fk_user from view_contract_purchaser";
        $r = $conn->query($sql);
        $tab = $r->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tab as $id_user) {
            $user = $em->getRepository('App\Entity\User')->find($id_user);
            $em->getRepository('App\Entity\Payment')->emptyPayments($user, $contract);
            $sql = "SELECT 
                p.id_purchase
                FROM contract c
                LEFT JOIN distribution d ON d.date BETWEEN c.period_start AND c.period_end
                RIGHT JOIN product_distribution pd ON pd.fk_distribution = d.id_distribution
                INNER JOIN contract_product cp ON cp.fk_product = pd.fk_product AND cp.fk_contract = c.id_contract
                LEFT JOIN purchase p ON p.fk_product_distribution = pd.id_product_distribution
                WHERE c.id_contract=".$id_contract."
                AND p.fk_user=".$id_user;
            
            $r = $conn->query($sql);
            $ids_purchase = $r->fetchAll(\PDO::FETCH_COLUMN);

            $em->getRepository('App\Entity\Payment')->compute($user, $contract, $ids_purchase);
            $output->writeln($user->getLastname());
        }
        $output->writeln('OK');
    }

}