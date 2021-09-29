<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Util\PasswordGenerator;
class ContractAutoOpenCloseCommand extends Command {


    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
            ->setName('amap:auto-open-close')
            ->setDescription('A éxécuter toutes les heures (CRON). Ouvre et ferme automatiquement les contrats')
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

        $em->getRepository('App\Entity\Contract')->autoOpen();
        $em->getRepository('App\Entity\Contract')->autoClose();

    }


}
