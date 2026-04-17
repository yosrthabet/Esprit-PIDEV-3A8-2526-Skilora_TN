<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CvExperienceEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobTitle', TextType::class, [
                'label' => 'Intitulé du poste',
                'required' => false,
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('company', TextType::class, [
                'label' => 'Entreprise',
                'required' => false,
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('duration', TextType::class, [
                'label' => 'Durée / période',
                'required' => false,
                'attr' => [
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                    'placeholder' => 'ex. 2020 — 2023',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
