<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;
use App\Util\Utils;

class CheckTransferCommand extends Command {


    public function __construct(Environment $twig)
    {
        parent::__construct();
        $this->twig = $twig;
    }
   
    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
                ->setName('amap:check-transfer')
                ->setDescription('Envoie par mail au producteur les virements à vérifier.')
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
        

        //recherche des nouvelles déclarations de virement
        $payments = $em->getRepository('App\Entity\Payment')->getUncheckedVirementAllDatabase();
               

        foreach($payments as $mail => $virements) {
          $body = $this->twig->render('Emails/_virement_table.html.twig', [
                'virements' => $virements,
                'lien' => "https://".$virements[0]["nom_domaine"]."/validation_virements",
                'beneficiaire' => $virements[0]['beneficiaire']
            ]);
          $content = $this->twig->render('Emails/layout.html.twig', [
                'body' => $body
            ]);
            
        }
          
          $message = (new \Swift_Message())
            ->setSubject('easyamap : nouveaux virements à vérifier')
            ->setFrom(array('ne_pas_repondre@easyamap.fr' => "easyamap"))
            ->setTo($mail)
            ->setBody($content)
            ->setText(Utils::htmlToText($content));
           $mailer = $container->get('mailer');
           $v = $mailer->send($message);
           if ($v)
             echo 'Message envoyé à '.$mail.PHP_EOL;
           else
             echo 'Echec de l\'envoi de l\'e-mail à '.$mail.PHP_EOL;
           

      
        $output->writeln('Ok');
  
      }
}
