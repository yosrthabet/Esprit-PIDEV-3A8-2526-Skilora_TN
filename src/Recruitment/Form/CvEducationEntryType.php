<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CvEducationEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('degree', TextType::class, [
                'label' => 'Diplôme / formation',
                'required' => false,
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('institution', TextType::class, [
                'label' => 'Établissement',
                'required' => false,
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('year', TextType::class, [
                'label' => 'Année',
                'required' => false,
                'attr' => [
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                    'placeholder' => 'ex. 2022',
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
