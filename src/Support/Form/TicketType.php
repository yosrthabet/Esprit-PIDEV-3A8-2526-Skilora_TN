<?php

declare(strict_types=1);

namespace App\Support\Form;

use App\Enum\TicketCategory;
use App\Enum\TicketPriority;
use App\Support\Entity\SupportTicket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<SupportTicket> */
class TicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('subject', TextType::class, ['label' => 'Subject', 'attr' => ['placeholder' => 'Brief summary of your issue']])
            ->add('description', TextareaType::class, ['label' => 'Description', 'attr' => ['rows' => 7, 'placeholder' => 'Describe your issue in detail']])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => array_combine(
                    array_map(fn (TicketCategory $c) => $c->label(), TicketCategory::cases()),
                    TicketCategory::cases(),
                ),
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priority',
                'choices' => array_combine(
                    array_map(fn (TicketPriority $p) => $p->label(), TicketPriority::cases()),
                    TicketPriority::cases(),
                ),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => SupportTicket::class]);
    }
}
