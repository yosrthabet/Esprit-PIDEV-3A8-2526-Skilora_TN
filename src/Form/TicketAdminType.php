<?php

namespace App\Form;

use App\Entity\Ticket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TicketAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Open' => 'OPEN',
                    'In progress' => 'IN_PROGRESS',
                    'Resolved' => 'RESOLVED',
                    'Closed' => 'CLOSED',
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
            ->add('assignedTo', IntegerType::class, [
                'label' => 'Agent ID',
                'required' => false,
                'empty_data' => '',
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
