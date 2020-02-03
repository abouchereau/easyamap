<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class FarmType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label',TextType::class,          array('label' => 'Nom *',     'required' => true))
            ->add('productType',TextType::class,    array('label' => 'Type de produits * (ex : produits laitiers)','required' => true))
            ->add('checkPayableTo',TextType::class, array('label' => 'Chèques à l\'ordre de *','required' => true))
            ->add('paymentTypes',EntityType::class, array('class' => 'App\Entity\PaymentType','label' => 'Types de paiements acceptés','multiple'=>true,'expanded' => true,'required' => true))
            ->add('paymentFreqs',EntityType::class, array('class' => 'App\Entity\PaymentFreq','label' => 'Fréquences de paiements acceptées','multiple'=>true,'expanded' => true,'required' => true))
            ->add('equitable',CheckboxType::class,  array('label' => 'Lissage des paiements','required' => false))
            ->add('description',TextareaType::class,array('label' => 'Description','required' => false))
            ->add('link',TextType::class,           array('label' => 'Lien',       'required' => false))
            ->add('referents',EntityType::class,    array('class' => 'App\Entity\User', 'label' => 'Référent(s)', 'multiple' => true,'required' => false))
            ->add('fkUser',EntityType::class,       array('class' => 'App\Entity\User', 'label' => 'Compte producteur associé','required' => false));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Farm'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'amap_orderbundle_farm';
    }
}
