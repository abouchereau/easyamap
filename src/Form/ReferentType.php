<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class ReferentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fkFarm',EntityType::class,array('class' => 'App\Entity\Farm', 'label' => 'Producteur' ))
            ->add('fkUser',EntityType::class,array('class' => 'App\Entity\User', 'label' => 'Référent'))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Referent'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'amap_orderbundle_referent';
    }
}
