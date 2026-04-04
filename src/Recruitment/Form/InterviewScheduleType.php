<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use App\Recruitment\InterviewFormat;
use App\Recruitment\InterviewLifecycle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;

class InterviewScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $widgetAttr = ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring'];

        $builder
            ->add('scheduledAt', DateTimeType::class, [
                'label' => 'Date et heure de l’entretien',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable',
                // Ne pas fixer id ici : le thème Form rend déjà id="{{ id }}" ; un second id dans attr casse getElementById côté JS.
                'attr' => $widgetAttr,
                'constraints' => [new NotNull(message: 'Indiquez la date et l’heure.')],
            ])
            ->add('format', ChoiceType::class, [
                'label' => 'Type d’entretien',
                'choices' => array_flip(InterviewFormat::choices()),
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
                'constraints' => [new NotNull()],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => array_merge($widgetAttr, [
                    'placeholder' => 'Ex. Tunisie, Paris, Salle A, lien visio…',
                ]),
                'help' => 'Ville, adresse ou indication pour l’entretien (affiché au candidat).',
                'constraints' => [
                    new Length(max: 150, maxMessage: 'Le lieu ne peut pas dépasser {{ limit }} caractères.'),
                ],
            ])
            ->add('lifecycle', ChoiceType::class, [
                'label' => 'Statut de l’entretien',
                // Déjà au format Symfony : libellé => valeur stockée (SCHEDULED / COMPLETED). Ne pas array_flip :
                // un flip soumettrait « À venir » au lieu de SCHEDULED et l’enregistrement échouait toujours.
                'choices' => InterviewLifecycle::choices(),
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
                'constraints' => [new NotNull()],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
