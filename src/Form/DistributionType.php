<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class DistributionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('infoLivraison',TextareaType::class,['label' => 'Contrôles des livraisons','required' => false])
            ->add('infoDistribution',TextareaType::class,['label' => 'Erreurs de produits et produits restants','required' => false])
            ->add('infoDivers',TextareaType::class,['label' => 'Infos diverses','required' => false])
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Entity\Distribution'
        ));
    }
    /**
     * @return string
     */
    public function getName()
    {
        return 'amap_distribution';
    }
}
