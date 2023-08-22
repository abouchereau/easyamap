<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Util\PasswordGenerator;
class GeneratePasswordCommand extends Command {

   
    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
                ->setName('amap:generate-password')
                ->setDescription('Génère un mot de passe aux adhérents qui n\'en ont pas')
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

        $users = $em->getRepository('App\Entity\User')->getUsersWithoutPassword();
        $output->writeln(count($users).' mots de passe à générer');
        foreach ($users as $user)
        {
          $password = PasswordGenerator::make();
          $user->setPassword($password);          
          $em->persist($user);
        }
        $em->flush();
        $output->writeln('Ok');
    }

    
}
