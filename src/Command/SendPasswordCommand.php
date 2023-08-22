<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendPasswordCommand extends Command {

   
    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
                ->setName('amap:send-password')
                ->setDescription('Envoie par mail les mots de passe')
        ;
    }

    /**
     * Exécution de la commande
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) 
    {
        $container = $this->getApplication()->getKernel()->getContainer();
        $em = $container->get('doctrine')->getManager();
        $users = $em->getRepository('App\Entity\User')->getAllUsers();
        
        $url = 'http://contrats.la-riche-en-bio.com';//à modifier avant envoi !!
        
        foreach ($users as $user)
        {
          $msg = "Bonjour,

Vous pouvez maintenant réaliser vos commandes sur easyamap à cette adresse : 
".$url."
    
Voici vos identifiants :

Nom : 
".strtoupper($user->getLastname())."
    
Mot de passe : 
".$user->getPassword()."

Cordialement
easyamap";
          

          
          
          $message = (new \Swift_Message())
            ->setSubject('easyamap : identifiants connexion')
            ->setFrom(array('ne_pas_repondre@easyamap.fr' => "easyamap"))
          //  ->setFrom('anthonybouchereau@hotmail.com')
            ->setTo($user->getEmail())
            //->setTo('abouchereau@articque.com')
            ->setBody($msg);
           $mailer = $container->get('mailer');
           $v = $mailer->send($message);
           if ($v)
             echo 'Message envoyé à '.$user->getLastname().PHP_EOL;
           else
             echo 'Echec de l\'envoi de l\'e-mail à '.$user->getLastname().PHP_EOL;
           

      }
       $output->writeln('Ok');
    }
}
