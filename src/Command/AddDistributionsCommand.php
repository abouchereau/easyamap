<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddDistributionsCommand extends Command {

   
    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
                ->setName('amap:add-distributions')
                ->setDescription('Génère un mot de passe aux adhérents qui n\'en ont pas')
                ->addArgument('day_of_week', InputArgument::REQUIRED, 'Jour de la semaine 1=lundi, 2=mardi...')
                ->addArgument('from', InputArgument::REQUIRED, 'Date de départ au format YYYY-MM-DD')
                ->addArgument('to', InputArgument::REQUIRED, 'Date de fin au format YYYY-MM-DD')
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
        
        $day_of_week = $input->getArgument('day_of_week');
        $from_date = $input->getArgument('from');
        $to_date = $input->getArgument('to');
        
        $nb = $em->getRepository('App\Entity\Distribution')->activeAllDayOfWeek($day_of_week, $from_date, $to_date);

        $output->writeln($nb.' distributions ajoutées');
    }

    
}
