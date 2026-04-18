<?php

namespace App\Recruitment\Form;

use App\Recruitment\Entity\JobOffer;
use App\Recruitment\WorkTypeCatalog;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class JobOfferType extends AbstractType
{
    private const INPUT_CLASS = 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-sm transition-colors placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

    private const TEXTAREA_CLASS = 'flex min-h-[120px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

    /** Aligné sur {@see JobOffer} title length. */
    private const TITLE_MAX = 100;

    /** Description : saisie minimale pour éviter les offres vides ; max cohérent avec TEXT. */
    private const DESCRIPTION_MIN = 50;

    private const DESCRIPTION_MAX = 8000;

    private const TEXT_BLOCK_MAX = 5000;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ic = self::INPUT_CLASS;

        $ta = self::TEXTAREA_CLASS;

        $builder
            ->add('title', TextType::class, [
                'label' => 'Intitulé du poste',
                'empty_data' => '',
                'attr' => [
                    'class' => $ic,
                    'maxlength' => self::TITLE_MAX,
                    'minlength' => 3,
                    'required' => true,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'L’intitulé du poste est obligatoire.']),
                    new Length([
                        'min' => 3,
                        'max' => self::TITLE_MAX,
                        'minMessage' => 'L’intitulé doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'L’intitulé ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => $ta,
                    'rows' => 6,
                    'minlength' => self::DESCRIPTION_MIN,
                    'maxlength' => self::DESCRIPTION_MAX,
                    'required' => true,
                ],
                'constraints' => [
                    new NotBlank(['message' => 'La description est obligatoire.']),
                    new Length([
                        'min' => self::DESCRIPTION_MIN,
                        'max' => self::DESCRIPTION_MAX,
                        'minMessage' => 'La description doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'La description ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('requirements', TextareaType::class, [
                'label' => 'Exigences',
                'required' => false,
                'attr' => [
                    'class' => str_replace('min-h-[120px]', 'min-h-[80px]', $ta),
                    'rows' => 4,
                    'maxlength' => self::TEXT_BLOCK_MAX,
                ],
                'constraints' => [
                    new Length([
                        'max' => self::TEXT_BLOCK_MAX,
                        'maxMessage' => 'Les exigences ne peuvent pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('minSalary', NumberType::class, [
                'label' => 'Salaire minimum',
                'required' => false,
                'scale' => 2,
                'attr' => ['class' => $ic, 'step' => '0.01', 'min' => 0],
                'constraints' => [
                    new Range(
                        notInRangeMessage: 'Indiquez un salaire minimum entre {{ min }} et {{ max }}.',
                        min: 0,
                        max: 99999999.99,
                    ),
                ],
            ])
            ->add('maxSalary', NumberType::class, [
                'label' => 'Salaire maximum',
                'required' => false,
                'scale' => 2,
                'attr' => ['class' => $ic, 'step' => '0.01', 'min' => 0],
                'constraints' => [
                    new Range(
                        notInRangeMessage: 'Indiquez un salaire maximum entre {{ min }} et {{ max }}.',
                        min: 0,
                        max: 99999999.99,
                    ),
                ],
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => [
                    'EUR' => 'EUR',
                    'TND' => 'TND',
                    'USD' => 'USD',
                ],
                'attr' => ['class' => $ic],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'empty_data' => '',
                'attr' => ['class' => $ic, 'maxlength' => 100],
                'constraints' => [
                    new Length([
                        'max' => 100,
                        'maxMessage' => 'Le lieu ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('workType', ChoiceType::class, [
                'label' => 'Type de travail',
                'required' => false,
                'placeholder' => 'Choisir…',
                'choices' => array_flip(WorkTypeCatalog::LABELS_FR),
                'attr' => ['class' => $ic],
            ])
            ->add('deadline', DateType::class, [
                'label' => 'Date limite de candidature',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => ['class' => $ic],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Ouverte' => 'OPEN',
                    'Fermée' => 'CLOSED',
                    'Brouillon' => 'DRAFT',
                ],
                'attr' => ['class' => $ic],
            ])
            ->add('experienceLevel', TextType::class, [
                'label' => 'Niveau d’expérience',
                'required' => false,
                'empty_data' => '',
                'attr' => ['class' => $ic, 'maxlength' => 30, 'placeholder' => 'ex. Junior, Confirmé, Senior'],
                'constraints' => [
                    new Length([
                        'max' => 30,
                        'maxMessage' => 'Le niveau d’expérience ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('skillsRequired', TextareaType::class, [
                'label' => 'Compétences requises',
                'required' => false,
                'attr' => [
                    'class' => str_replace('min-h-[120px]', 'min-h-[70px]', $ta),
                    'rows' => 3,
                    'maxlength' => self::TEXT_BLOCK_MAX,
                ],
                'constraints' => [
                    new Length([
                        'max' => self::TEXT_BLOCK_MAX,
                        'maxMessage' => 'Ce champ ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('benefits', TextareaType::class, [
                'label' => 'Avantages',
                'required' => false,
                'attr' => [
                    'class' => str_replace('min-h-[120px]', 'min-h-[70px]', $ta),
                    'rows' => 3,
                    'maxlength' => self::TEXT_BLOCK_MAX,
                ],
                'constraints' => [
                    new Length([
                        'max' => self::TEXT_BLOCK_MAX,
                        'maxMessage' => 'Ce champ ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('isFeatured', CheckboxType::class, [
                'label' => 'Mise en avant',
                'required' => false,
                'attr' => ['class' => 'rounded border-input'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobOffer::class,
        ]);
    }
}
