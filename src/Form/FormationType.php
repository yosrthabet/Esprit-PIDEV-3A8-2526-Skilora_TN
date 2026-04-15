<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Formation;
use App\Enum\FormationLevel;
use App\Validation\ValidationGroups;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Validation is enforced on {@see Formation} (Validator component).
 * This type only configures widgets, labels, and HTML attributes.
 */
final class FormationType extends AbstractType
{
    private const INPUT = 'flex h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground placeholder:text-muted-foreground shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

    private const TEXTAREA = 'flex min-h-[140px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Ex: Java Spring Boot Masterclass',
                    'class' => self::INPUT,
                    'maxlength' => 255,
                    'minlength' => 3,
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
                    'maxlength' => 500,
                    'minlength' => 20,
                ],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ])
            ->add('price', NumberType::class, [
                'label' => 'Prix',
                'required' => false,
                'empty_data' => null,
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'placeholder' => 'Optional — leave empty or 0 for free',
                    'class' => self::INPUT,
                    'min' => 0,
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
                    'min' => 1,
                    'step' => 1,
                    'inputmode' => 'numeric',
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
                    'min' => 1,
                    'step' => 1,
                    'inputmode' => 'numeric',
                ],
                'label_attr' => ['class' => 'text-sm font-medium'],
            ])
            ->add('level', EnumType::class, [
                'class' => FormationLevel::class,
                'label' => 'Niveau',
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'formation_edit',
            'validation_groups' => static function (FormInterface $form): array {
                $data = $form->getData();
                if ($data instanceof Formation && null !== $data->getId()) {
                    return [ValidationGroups::FORMATION_UPDATE];
                }

                return [ValidationGroups::FORMATION_CREATE];
            },
        ]);
    }
}
