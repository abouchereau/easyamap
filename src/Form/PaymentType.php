<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Repository\FarmRepository;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class PaymentType extends AbstractType
{

    
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fkUser',EntityType::class,   array(
              'label' => 'Adhérent *',
              'required' => true,
              'class' => 'App\Entity\User'))
            ->add('fkFarm',EntityType::class,   array(
              'label' => 'Producteur *',
              'class' => 'App\Entity\Farm',              
              'required' => true,
              'query_builder' => function (FarmRepository $er) use ($options)
              {
                  if ($options['user']->getIsAdmin())
                  {
                    return $er->createQueryBuilder('f');
                  }
                  else
                  {
                    return $er->createQueryBuilder('f')
                      ->leftJoin('App\Entity\Referent','r','WITH','r.fkFarm = f.idFarm')
                      ->where('r.fkUser = :user')
                      ->setParameter('user', $options['user']);
                  }
              }
              
              ))            
            ->add('amount',MoneyType::class,array('label' => 'Montant *','required' => true,'currency' =>'EUR','scale' => 2))
            ->add('received',MoneyType::class,array('label' => 'Reçu *','required' => true,'currency' =>'EUR','scale' => 2))
            ->add('description',TextareaType::class, array('label' => 'Description','required' => false))
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
