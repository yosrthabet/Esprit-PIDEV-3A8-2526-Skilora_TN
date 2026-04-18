<?php

declare(strict_types=1);

namespace App\Form\Formation;

use App\Entity\Formation;
use App\Enum\FormationLevel;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Separates formation catalogue metadata from certificate branding form fields (SOLID: open for extension).
 */
final class FormationFormConfigurator
{
    private const INPUT = 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground placeholder:text-muted-foreground shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

    private const TEXTAREA = 'flex min-h-[140px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

    public function configureMetadata(FormBuilderInterface $builder): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ex: Java Spring Boot Masterclass',
                    'class' => self::INPUT,
                    'minlength' => 3,
                    'maxlength' => 255,
                    'autocomplete' => 'off',
                ],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Describe the course (20–500 characters)…',
                    'class' => self::TEXTAREA,
                    'rows' => 6,
                    'minlength' => 20,
                    'maxlength' => 500,
                ],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'required' => false,
                'empty_data' => null,
                'scale' => 2,
                'html5' => false,
                'attr' => [
                    'placeholder' => 'Optional — leave empty or 0 for free',
                    'class' => self::INPUT,
                    'step' => '0.01',
                    'inputmode' => 'decimal',
                ],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ])
            ->add('duration', IntegerType::class, [
                'label' => 'Durée',
                'required' => true,
                'empty_data' => null,
                'invalid_message' => 'formation.form.invalid_integer',
                'attr' => [
                    'placeholder' => '40',
                    'class' => self::INPUT,
                    'inputmode' => 'numeric',
                    'min' => 1,
                ],
                'label_attr' => ['class' => 'text-sm font-medium'],
                'help' => 'Total duration as a whole number (e.g. hours).',
            ])
            ->add('lessonsCount', IntegerType::class, [
                'label' => 'Nombre de leçons',
                'required' => true,
                'empty_data' => null,
                'invalid_message' => 'formation.form.invalid_integer',
                'attr' => [
                    'placeholder' => '10',
                    'class' => self::INPUT,
                    'inputmode' => 'numeric',
                    'min' => 1,
                ],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ])
            ->add('level', EnumType::class, [
                'class' => FormationLevel::class,
                'label' => 'Niveau',
                'required' => true,
                'choice_label' => static fn (FormationLevel $l): string => $l->labelFr(),
                'attr' => ['class' => self::INPUT],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Catégorie',
                'required' => true,
                'choices' => array_flip(Formation::CATEGORY_LABELS_FR),
                'placeholder' => false,
                'attr' => ['class' => self::INPUT],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ]);
    }

    public function configureCertificateBranding(FormBuilderInterface $builder, array $formOptions): void
    {
        $showRemove = (bool) ($formOptions['show_signature_remove_checkbox'] ?? false);

        $builder->add('signatureData', HiddenType::class, [
            'label' => false,
            'mapped' => false,
            'required' => false,
            'attr' => [
                'x-ref' => 'signatureData',
            ],
        ]);

        if ($showRemove) {
            $builder->add('removeCertificateSignature', CheckboxType::class, [
                'label' => 'Supprimer la signature actuelle du certificat',
                'mapped' => false,
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 rounded border border-input bg-background text-primary'],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ]);
        }
    }
}
