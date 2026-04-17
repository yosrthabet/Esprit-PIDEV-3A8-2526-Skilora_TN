<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use App\Recruitment\InterviewFormat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Saisie planification : les contraintes métier sont sur {@see \App\Recruitment\Entity\JobInterview}
 * (groupe {@see \App\Recruitment\Entity\JobInterview::VALIDATION_GROUP_SCHEDULE}).
 */
class InterviewScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $widgetAttr = ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring'];

        $builder
            ->add('interviewDate', DateType::class, [
                'label' => 'Date',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable',
                'attr' => $widgetAttr,
            ])
            ->add('interviewTime', TimeType::class, [
                'label' => 'Heure',
                'widget' => 'single_text',
                'html5' => true,
                'input' => 'datetime_immutable',
                'attr' => $widgetAttr,
            ])
            ->add('format', ChoiceType::class, [
                'label' => 'Type',
                'choices' => array_flip(InterviewFormat::choices()),
                'attr' => ['class' => 'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => array_merge($widgetAttr, [
                    'placeholder' => 'Adresse, ville ou salle (obligatoire si présentiel)',
                    'data-location-field' => '1',
                ]),
                'help' => 'Obligatoire uniquement pour « Présentiel ». Laisser vide pour un entretien en ligne.',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Note (optionnel)',
                'required' => false,
                'attr' => array_merge($widgetAttr, ['rows' => 3, 'class' => 'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm']),
                'help' => 'Consignes, lien de visio, précisions pour le candidat.',
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }
            if (($data['format'] ?? '') === InterviewFormat::ONLINE) {
                $data['location'] = '';
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
