<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CvBuilderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, [
                'label' => 'Nom complet',
                'constraints' => [
                    new NotBlank(message: 'Indiquez votre nom.'),
                    new Length(max: 200),
                ],
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [
                    new NotBlank(message: 'Indiquez votre e-mail.'),
                    new Email(),
                ],
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('phone', TextType::class, [
                'label' => 'Téléphone',
                'required' => false,
                'constraints' => [new Length(max: 50)],
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Adresse',
                'required' => false,
                'constraints' => [new Length(max: 500)],
                'attr' => [
                    'rows' => 2,
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                ],
            ])
            ->add('professionalSummary', TextareaType::class, [
                'label' => 'Profil professionnel (résumé)',
                'constraints' => [
                    new NotBlank(message: 'Rédigez un court résumé.'),
                    new Length(max: 4000),
                ],
                'attr' => [
                    'rows' => 5,
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                    'placeholder' => 'Quelques lignes sur votre parcours et vos objectifs…',
                ],
            ])
            ->add('education', CollectionType::class, [
                'label' => 'Formation',
                'entry_type' => CvEducationEntryType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'attr' => ['class' => 'space-y-4'],
            ])
            ->add('experience', CollectionType::class, [
                'label' => 'Expérience professionnelle',
                'entry_type' => CvExperienceEntryType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'by_reference' => false,
                'attr' => ['class' => 'space-y-4'],
            ])
            ->add('skills', TextareaType::class, [
                'label' => 'Compétences',
                'help' => 'Séparez les compétences par des virgules ou des retours à la ligne.',
                'constraints' => [
                    new NotBlank(message: 'Indiquez au moins une compétence.'),
                    new Length(max: 2000),
                ],
                'attr' => [
                    'rows' => 3,
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                    'placeholder' => 'ex. PHP, Symfony, MySQL, Git',
                ],
            ])
            ->add('languages', TextareaType::class, [
                'label' => 'Langues (facultatif)',
                'required' => false,
                'constraints' => [new Length(max: 500)],
                'attr' => [
                    'rows' => 2,
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                    'placeholder' => 'ex. Français (natif), Anglais (B2)',
                ],
            ])
            ->add('template', ChoiceType::class, [
                'label' => 'Modèle',
                'choices' => [
                    'Moderne (bandeau)' => 'modern',
                    'Classique (centré)' => 'classic',
                ],
                'expanded' => true,
                'attr' => ['class' => 'flex flex-wrap gap-4'],
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil (facultatif)',
                'required' => false,
                'mapped' => false,
                'constraints' => [
                    new File(
                        maxSize: '2M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Formats acceptés : JPEG, PNG, WebP.',
                    ),
                ],
                'attr' => [
                    'class' => 'block w-full text-sm text-muted-foreground file:mr-4 file:rounded-lg file:border-0 file:bg-primary file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-foreground',
                ],
            ])
            ->add('download', SubmitType::class, [
                'label' => 'Télécharger en PDF',
                'attr' => [
                    'class' => 'candidate-font-display inline-flex h-12 items-center justify-center rounded-2xl bg-primary px-8 text-sm font-semibold text-primary-foreground shadow-md transition hover:bg-primary/90',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'Enregistrer pour mes candidatures',
                'attr' => [
                    'class' => 'inline-flex h-12 items-center justify-center rounded-2xl border border-input bg-background px-8 text-sm font-semibold transition hover:bg-accent',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'cv_builder',
        ]);
    }
}
