<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class SettingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name',TextType::class, array('label' => 'Nom','required' => false))
            ->add('link',TextType::class, array('label' => 'Lien','required' => false))
            ->add('logoSmallUrl',TextType::class, array('label' => 'URL petit logo (90px)','required' => false))
            ->add('logoLargeUrl',TextType::class, array('label' => 'URL grand logo (256px)','required' => false))
            ->add('logoSecondary',TextType::class, array('label' => 'URL logo secondaire (90px)','required' => false))
            ->add('useAddress',CheckboxType::class,  array('label' => 'OPTION - Coordonnées adhérents','required' => false))
            //->add('cotisation',CheckboxType::class,  array('label' => 'Gestion des cotisations','required' => false))              
            ->add('useReport',CheckboxType::class,  array('label' => 'OPTION - Compte-rendu de distributions','required' => false)) 
            ->add('registerDistribution',CheckboxType::class,  array('label' => 'OPTION - Inscription aux distributions','required' => false))  
            ->add('textRegisterDistribution',TextareaType::class,array('label' => 'Texte Inscription Distribution','required' => false))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Setting'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'amap_orderbundle_setting';
    }
}
