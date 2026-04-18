<?php

namespace App\Form\Finance;

use App\Entity\Finance\BankAccount;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Contraintes métier : entité BankAccount uniquement (évite les messages en double).
 */
class BankAccountType extends AbstractType
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
                'label_attr' => ['class' => 'text-sm font-medium'],
            ])
            ->add('bankName', TextType::class, [
                'label' => 'Banque',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nom de la banque',
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                ],
            ])
            ->add('iban', TextType::class, [
                'label' => 'IBAN',
                'required' => true,
                'attr' => [
                    'placeholder' => 'IBAN',
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                ],
            ])
            ->add('swift', TextType::class, [
                'label' => 'SWIFT / BIC',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ex. DEUTDEFF ou 8–11 caractères',
                    'class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm',
                ],
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => [
                    'TND' => 'TND',
                    'EUR' => 'EUR',
                    'USD' => 'USD',
                ],
                'placeholder' => 'Choisir une devise',
                'required' => true,
                'attr' => ['class' => 'w-full rounded-lg border border-input bg-background px-3 py-2 text-sm'],
            ])
            ->add('isPrimary', CheckboxType::class, [
                'label' => 'Compte principal',
                'required' => false,
            ])
            ->add('isVerified', CheckboxType::class, [
                'label' => 'Vérifié',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BankAccount::class,
        ]);
    }
}
