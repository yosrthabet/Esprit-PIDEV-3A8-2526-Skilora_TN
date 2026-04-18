<?php

namespace App\Form\Finance;

use App\Entity\Finance\Bonus;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contraintes métier : entité Bonus uniquement (évite les messages en double).
 */
class BonusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => ($u->getFullName() ?: $u->getUsername()).' ('.$u->getUsername().')',
                'label' => 'Employé',
                'placeholder' => 'Choisir un employé',
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('u')->orderBy('u.fullName', 'ASC'),
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('amount', NumberType::class, [
                'label' => 'Montant (TND)',
                'scale' => 2,
                'html5' => true,
                'required' => true,
                'invalid_message' => 'Nombre invalide.',
                'attr' => [
                    'placeholder' => 'Montant',
                    'inputmode' => 'decimal',
                    'min' => 0.01,
                    'step' => 'any',
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                ],
            ])
            ->add('reason', TextareaType::class, [
                'label' => 'Motif',
                'required' => true,
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Motif de la prime',
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                ],
            ])
            ->add('dateAwarded', DateType::class, [
                'label' => 'Date d’attribution',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
                'invalid_message' => 'Date invalide.',
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Bonus::class,
        ]);
    }
}
