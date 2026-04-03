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
        $payments = $em->getRepository('App\Entity\Payment')->getIssuedVirementAllDatabase();
              
        foreach($payments as $mail => $virements) {
          $body = $this->twig->render('Emails/_virement_table.html.twig', [
                'virements' => $virements,
                'lien' => "https://".$virements[0]["nom_domaine"]."/validation_virements/a_valider",
                'beneficiaire' => $virements[0]['beneficiaire']
            ]);
          $content = $this->twig->render('Emails/layout.html.twig', [
                'body' => $body
            ]);
            

          $referents_email = [];
          foreach($virements as $virement) {
            if (!empty($virement['referents_email'])) {
              $referents_email = array_merge($referents_email, array_filter(explode(',',$virement['referents_email'])));
            }
          }
           die(print_r($referents_email,1));
          $message = (new \Swift_Message())
            ->setSubject('easyamap : '.count($virements).' virement'.(count($virements) > 1 ? 's' : '').' à vérifier')
            ->setFrom(array('ne_pas_repondre@easyamap.fr' => "easyamap"))
            ->setTo($mail)
            ->setCc($referents_email)
            ->setBody($content, 'text/html')
            ->addPart(Utils::htmlToText($content), 'text/plain');
           $mailer = $container->get('mailer');
           $v = $mailer->send($message);
           if ($v) {
             echo 'Message envoyé à '.$mail.PHP_EOL;
           }
           else {
             echo 'Echec de l\'envoi de l\'e-mail à '.$mail.PHP_EOL;
           }

      
        $output->writeln('Ok');
  
      }
    }
}
