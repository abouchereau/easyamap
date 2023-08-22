<?php
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Amap\OrderBundle\Entity\PaymentSplit;

class SplitPaymentCommand extends Command {

   
    /**
     * Déclaration de la commande
     */
    protected function configure() {
        $this
                ->setName('amap:split-payment')
                ->setDescription('Splitte les paiements')
        ;
    }

    /**
     * Exécution de la commande
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output) 
    {
        $this->manageSeveralSplits();
        $this->manageOneSplit();
        $this->manageDescription();
          
        echo $this->checkEquality();
    }
    
    private function manageSeveralSplits() {
        //1 seul possibilité / plusieurs chèques
        $container = $this->getApplication()->getKernel()->getContainer();
        $em = $container->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $sql = "SELECT p.id_payment, p.description, c.period_start
               FROM payment p
               left join contract c on c.id_contract = p.fk_contract
               where p.description not like '% OU %'
               and p.description like '%chèques%'";
        $r = $conn->query($sql);
        $payment2split = $r->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($payment2split as $payment) {
            $date = \DateTime::createFromFormat('Y-m-d', $payment['period_start']);
            preg_match_all("#[0-9]{1,3},[0-9]{2}#",$payment['description'],$matches);
            foreach($matches[0] as $match) {
                $p = $em->getRepository('App\Entity\Payment')->find($payment['id_payment']);
                $ps = new PaymentSplit();
                $ps->setAmount((float)str_replace(',','.',$match));
                $date_copy = clone $date;
                $ps->setDate($date_copy);                
                $ps->setFkPayment($p);
                $em->persist($ps);
                $date->add(new \DateInterval('P1M'));

            }
        }
        $em->flush();
    }
    
    private function manageOneSplit() {
        // plusieurs possibilités (dont 1 seul chèque) ou 1 seul chèque
        $container = $this->getApplication()->getKernel()->getContainer();
        $em = $container->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $sql = "INSERT INTO payment_split(fk_payment, amount, date)
            SELECT p.id_payment, p.amount, c.period_start
               FROM payment p
               left join contract c on c.id_contract = p.fk_contract
               where p.description like '% OU %'
               or p.description not like '%chèques%'";
        $conn->exec($sql);
    }
    
    private function checkEquality() {
        $container = $this->getApplication()->getKernel()->getContainer();
        $em = $container->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $sql = 'select 
                case when a = b then "OK" else "KO" end as equality
                from (
                select round(sum(p.amount),2) as a
                from payment p) t1,
                (select round(sum(ps.amount),2) as b
                from payment_split ps) t2';
        $r = $conn->query($sql);
        return $r->fetch(\PDO::FETCH_COLUMN).PHP_EOL;
    }
    
    private function manageDescription() {
        $container = $this->getApplication()->getKernel()->getContainer();
        $em = $container->get('doctrine')->getManager();
        $conn = $em->getConnection();
        $sql = "SELECT p.id_payment, p.description, p.amount, p.received, c.period_start as date
               FROM payment p
               left join contract c on c.id_contract = p.fk_contract";
        $r = $conn->query($sql);
        $tab = $r->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($tab as $each) {
            $details = array();
            $choisi = array();
            $tmp = explode('OU', $each['description']);                
            foreach($tmp as $div) {
                $detail = $this->detail($div, $each['date']);
                if (!in_array($detail, $details))
                    $details[] = $detail;
                $choisi[] = (count($choisi)==0 && $each['amount']>0 ? 1:0);
            }
            usort($details, function ($a,$b) {return count($a) > count($b);});
            
            $tmp = explode('ordre de ',$each['description']);
            $ordre = "";
            if (isset($tmp[1]))
                $ordre = $tmp[1];
            $new = array(
                array(1),//paiement chèque uniquement
                $details,//détail //0: date, 1 : amount, 2: has_ratio, 3: monthly
                array($ordre),//ordre
                $choisi//choisi
            );
            $payment = $em->getRepository('App\Entity\Payment')->find($each['id_payment']);
            $payment->setDescription(json_encode($new));
            $em->persist($payment);
            $em->flush();
            
        }
    }
    
    private function detail($str, $date_ini) {
        preg_match_all("#[0-9]{1,3},[0-9]{2}#",$str,$matches);
        $detail = array();
        $date = \DateTime::createFromFormat('Y-m-d', substr($date_ini,0,8).'01');
        if (count($matches[0])==0) {
            $date_copy = clone $date;
            $detail[] = array($date_copy->format('Y-m-d'),0,0,2);
        } else {
            foreach ($matches[0] as $amount) {
                $date_copy = clone $date;
                $detail[] = array($date_copy->format('Y-m-d'),(1.0*str_replace(",",".",$amount)),0,2);
                $date->add(new \DateInterval('P1M'));                
            }
        }
        return $detail;
    }
    
}
