<?php

namespace App\Form\Finance;

use App\Recruitment\Entity\Company;
use App\Entity\Finance\Contract;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contraintes métier : uniquement sur l’entité Contract (évite les messages en double).
 */
class ContractType extends AbstractType
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
            ->add('company', EntityType::class, [
                'class' => Company::class,
                'choice_label' => 'name',
                'label' => 'Entreprise',
                'placeholder' => 'Choisir une entreprise',
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de contrat',
                'choices' => [
                    'Permanent' => 'PERMANENT',
                    'Freelance' => 'FREELANCE',
                    'Internship' => 'INTERNSHIP',
                    'CDD' => 'CDD',
                    'CDI' => 'CDI',
                ],
                'placeholder' => 'Choisir un type',
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('position', TextType::class, [
                'label' => 'Poste',
                'attr' => [
                    'placeholder' => 'Intitulé du poste',
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                ],
            ])
            ->add('salary', NumberType::class, [
                'label' => 'Salaire de base (TND)',
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
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => true,
                'invalid_message' => 'Date de début invalide.',
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'invalid_message' => 'Date de fin invalide.',
                'required' => true,
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'Active' => 'ACTIVE',
                    'Expired' => 'EXPIRED',
                    'Terminated' => 'TERMINATED',
                    'Pending' => 'PENDING',
                ],
                'placeholder' => 'Choisir un statut',
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contract::class,
        ]);
    }
}
