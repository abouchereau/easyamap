<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\FarmRepository;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class PaymentEditType extends AbstractType
{

    
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fkUser',EntityType::class, array(
              'label' => 'Adhérent',              
              'disabled' => true,
              'attr'=> array('class'=>'not-select2'),
              'class' => 'App\Entity\User'))
            ->add('fkFarm',EntityType::class, array(
              'label' => 'Producteur',
              'disabled' => true,
              'attr'=> array('class'=>'not-select2'),
              'class' => 'App\Entity\Farm'
            ))            
            ->add('paymentType',EntityType::class, array(
              'label' => 'Type de paiement',
              'attr'=> array('class'=>'not-select2'),
              'class' => 'App\Entity\PaymentType'
            ))            
            ->add('amount',MoneyType::class,array('label' => 'Montant en €','currency' =>'','scale' => 2))
            ->add('received',MoneyType::class,array('label' => 'Montant reçu en € ','currency' =>'','scale' => 2))
            ->add('issuedAt',DateType::class,array('label' => 'Emis le',   'widget' => 'single_text'))
            ->add('receivedAt',DateType::class,array('label' => 'Reçu le',   'widget' => 'single_text'))
      //      ->add('description',TextareaType::class, array('label' => 'Description','required' => false))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Payment'
        ));
    }
    
    public function configureOptions( OptionsResolver $resolver ) {
        $resolver->setDefaults( [
          'user' => null,
          'selectedFarm' => null,
          'selectedUser' => null,
        ] );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'amap_orderbundle_payment';
    }
}
