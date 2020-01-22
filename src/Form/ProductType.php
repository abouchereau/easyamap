<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ProductType extends AbstractType
{

  
        /**
     * @param FormBuilderInterface $builder
     * @param array $options     */ 
  
  
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label',TextType::class,     array('label' => 'Nom *',     'required' => true))
            ->add('unit',TextType::class,      array('label' => 'Conditionnement (exemple : 200g)','required' => false))
            ->add('basePrice',MoneyType::class,array('label' => 'Prix en € * (exemple: 3,20)','required' => true,'currency' =>'','scale' => 2))
            ->add('ratio',CheckboxType::class,array('label' => 'Au poids (montant calculé à la livraison)','required' => false))
            ->add('isSubscription',CheckboxType::class,array('label' => 'Mode Abonnement','required' => false))
            ->add('isCertified',CheckboxType::class,array('label' => 'Certifié Bio','required' => false))
            ->add('description',TextareaType::class,array('label' => 'Description','required' => false))
            ->add('fkFarm',EntityType::class,   array(
              'label' => 'Producteur',
              'class' => 'App\Entity\Farm',              
              'required' => true,
              'query_builder' => function (EntityRepository $er) use ($options)
              {
                  if ($options['user']->getIsAdmin())
                  {
                    return $er->createQueryBuilder('f')->orderBy('f.sequence', 'ASC');
                  }
                  else
                  {
                    return $er->createQueryBuilder('f')
                      ->leftJoin('App\Entity\Referent','r','WITH','r.fkFarm = f.idFarm')
                      ->where('r.fkUser = :user')
                      ->orderBy('f.sequence', 'ASC')      
                      ->setParameter('user', $options['user']);
                  }
              }
              
              ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Product'
        ));
    }

    public function configureOptions( OptionsResolver $resolver ) {
        $resolver->setDefaults( [
          'user' => null
        ] );
    }
    /**
     * @return string
     */
    public function getName()
    {
        return 'amap_orderbundle_product';
    }
}
