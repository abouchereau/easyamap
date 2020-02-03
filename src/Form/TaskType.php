<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class TaskType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label',TextType::class,array('label' => 'Libellé','required' => true))
            ->add('isActive',CheckboxType::class,array('label' => 'Actif','required' => false))                
            ->add('min',NumberType::class,array('label' => 'Nombre minimum de personnes','required' => false))                
            ->add('max',NumberType::class,array('label' => 'Nombre maximum de personnes','required' => false))                
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Task'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'amap_orderbundle_task';
    }
}
