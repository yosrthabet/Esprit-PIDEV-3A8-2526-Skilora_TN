<?php

namespace App\Form;

use App\Entity\CommunityGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunityGroupType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom du groupe',
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Nom du groupe…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 2000,
                    'placeholder' => 'Décrivez votre groupe…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie (optionnel)',
                'required' => false,
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'Technologie, Design, Marketing…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('coverImageUrl', UrlType::class, [
                'label' => 'Image de couverture (URL, optionnel)',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://exemple.com/image.jpg',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('isPublic', CheckboxType::class, [
                'label' => 'Groupe public (visible par tous)',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 rounded border-border text-primary focus:ring-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommunityGroup::class,
        ]);
    }
}
