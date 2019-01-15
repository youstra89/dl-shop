<?php

namespace App\Form;

use App\Entity\Product;
use App\Entity\Category;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class ProductType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('type',        TextType::class, ['required' => true,  'label' => false])
            ->add('description', TextType::class, ['required' => false, 'label' => false])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event)
        {
          $product = $event->getData();
          $form   = $event->getForm();

          if (!$product || null === $product->getId()) {
              $form->add('category',    EntityType::class, [
                'required' => false,
                'class'    => Category::class,
                'choice_label' => 'name',
                'label'    => false,
                'required' => true,
                'multiple' => false,
                'placeholder' => 'Sélectionnez une catégorie'
              ]);
          }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
