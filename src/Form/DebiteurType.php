<?php

namespace App\Form;

use App\Entity\Debiteur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DebiteurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name',         TextType::class, ['required' => true,  'label' => 'Nom et prénom du client'])
            ->add('phone_number', TextType::class, ['required' => true,  'label' => 'Numéro de téléphone du client'])
            ->add('comment',      TextType::class, ['required' => false, 'label' => 'Autres informations'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Debiteur::class,
        ]);
    }
}
