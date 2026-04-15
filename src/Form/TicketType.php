<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, ['label' => 'Sujet'])
            ->add('category', ChoiceType::class, [
                'label' => 'Categorie',
                'choices' => [
                    'Technique' => 'TECHNIQUE',
                    'Facturation' => 'FACTURATION',
                    'Compte' => 'COMPTE',
                    'Autre' => 'AUTRE',
                ],
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorite',
                'choices' => [
                    'Low' => 'LOW',
                    'Medium' => 'MEDIUM',
                    'High' => 'HIGH',
                    'Urgent' => 'URGENT',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 6],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Ticket::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
