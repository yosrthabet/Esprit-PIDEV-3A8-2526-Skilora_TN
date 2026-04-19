<?php

namespace App\Form;

use App\Entity\CommunityEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunityEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'événement',
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Nom de l\'événement…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'maxlength' => 5000,
                    'placeholder' => 'Décrivez votre événement…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('eventType', ChoiceType::class, [
                'label' => 'Type d\'événement',
                'choices' => CommunityEvent::TYPES,
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Adresse ou lieu…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('isOnline', CheckboxType::class, [
                'label' => 'Événement en ligne',
                'required' => false,
                'attr' => ['class' => 'h-4 w-4 rounded border-border text-primary focus:ring-primary'],
            ])
            ->add('onlineLink', UrlType::class, [
                'label' => 'Lien en ligne (optionnel)',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://meet.google.com/…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'Date de fin (optionnel)',
                'required' => false,
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('maxAttendees', IntegerType::class, [
                'label' => 'Nombre max de participants (0 = illimité)',
                'required' => false,
                'attr' => [
                    'min' => 0,
                    'placeholder' => '0',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('imageUrl', UrlType::class, [
                'label' => 'Image de l\'événement (URL, optionnel)',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://exemple.com/image.jpg',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommunityEvent::class,
        ]);
    }
}
