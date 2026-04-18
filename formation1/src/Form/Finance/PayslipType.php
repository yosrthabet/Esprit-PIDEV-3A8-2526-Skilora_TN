<?php

namespace App\Form\Finance;

use App\Entity\Finance\Payslip;
use App\Entity\User;
use App\Validation\Finance\FinanceAllowedValues;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire bulletin aligné UI JavaFX : heures sup × taux, CNSS/IRPP auto (calcul serveur + live JS).
 */
class PayslipType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $monthChoices = [];
        foreach (range(1, 12) as $m) {
            $monthChoices[sprintf('%02d — %s', $m, $this->monthLabel($m))] = $m;
        }

        $yearChoices = [];
        foreach (range((int) date('Y') - 2, (int) date('Y') + 2) as $y) {
            $yearChoices[(string) $y] = $y;
        }

        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => ($u->getFullName() ?: $u->getUsername()).' #'.$u->getId(),
                'label' => 'Employé',
                'required' => true,
                'placeholder' => 'Sélectionner ou rechercher un employé',
                'query_builder' => fn (EntityRepository $r) => $r->createQueryBuilder('u')->orderBy('u.fullName', 'ASC'),
                'attr' => [
                    'class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100',
                    'data-payslip-field' => 'user',
                ],
            ])
            ->add('month', ChoiceType::class, [
                'label' => 'Mois',
                'choices' => $monthChoices,
                'placeholder' => 'Mois',
                'required' => true,
                'attr' => ['class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100', 'data-payslip-field' => 'month'],
            ])
            ->add('year', ChoiceType::class, [
                'label' => 'Année',
                'choices' => $yearChoices,
                'placeholder' => 'Année',
                'required' => true,
                'attr' => ['class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100', 'data-payslip-field' => 'year'],
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Devise',
                'choices' => array_combine(FinanceAllowedValues::CURRENCIES, FinanceAllowedValues::CURRENCIES),
                'placeholder' => 'Devise',
                'required' => true,
                'attr' => ['class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100', 'data-payslip-field' => 'currency'],
            ])
            ->add('baseSalary', NumberType::class, [
                'label' => 'Salaire de base',
                'scale' => 2,
                'html5' => true,
                'required' => true,
                'invalid_message' => 'Nombre invalide.',
                'attr' => [
                    'placeholder' => 'Amount',
                    'min' => 0,
                    'step' => 'any',
                    'class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100',
                    'data-payslip-field' => 'baseSalary',
                ],
            ])
            ->add('overtimeHours', NumberType::class, [
                'label' => 'Heures supplémentaires',
                'scale' => 2,
                'required' => true,
                'empty_data' => '0',
                'invalid_message' => 'Nombre invalide.',
                'attr' => [
                    'placeholder' => 'Hours',
                    'min' => 0,
                    'class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100',
                    'data-payslip-field' => 'overtimeHours',
                ],
            ])
            ->add('overtimeRate', NumberType::class, [
                'label' => 'Taux horaire heures sup.',
                'scale' => 2,
                'required' => true,
                'empty_data' => '0',
                'mapped' => false,
                'invalid_message' => 'Nombre invalide.',
                'attr' => [
                    'placeholder' => 'Per Hour',
                    'min' => 0,
                    'step' => 'any',
                    'class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100',
                    'data-payslip-field' => 'overtimeRate',
                ],
            ])
            ->add('bonuses', NumberType::class, [
                'label' => 'Primes additionnelles',
                'scale' => 2,
                'required' => true,
                'empty_data' => '0',
                'invalid_message' => 'Nombre invalide.',
                'attr' => [
                    'placeholder' => 'Amount',
                    'min' => 0,
                    'class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100',
                    'data-payslip-field' => 'bonuses',
                ],
            ])
            ->add('otherDeductions', NumberType::class, [
                'label' => 'Autres retenues',
                'scale' => 2,
                'required' => true,
                'empty_data' => '0',
                'invalid_message' => 'Nombre invalide.',
                'attr' => [
                    'placeholder' => 'Amount',
                    'min' => 0,
                    'class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100',
                    'data-payslip-field' => 'otherDeductions',
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => [
                    'DRAFT' => 'DRAFT',
                    'PENDING' => 'PENDING',
                    'APPROVED' => 'APPROVED',
                    'PAID' => 'PAID',
                ],
                'placeholder' => 'Statut',
                'required' => true,
                'attr' => ['class' => 'w-full rounded-lg border border-zinc-600 bg-zinc-900/80 px-3 py-2 text-sm text-zinc-100', 'data-payslip-field' => 'status'],
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event): void {
            $p = $event->getData();
            if (!$p instanceof Payslip) {
                return;
            }
            $form = $event->getForm();
            $hours = (float) ($p->getOvertimeHours() ?? 0);
            $total = (float) ($p->getOvertimeTotal() ?? 0);
            if ($hours > 0 && $total >= 0) {
                $form->get('overtimeRate')->setData(round($total / $hours, 4));
            }
        });

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $p = $event->getData();
            if (!$p instanceof Payslip) {
                return;
            }
            $form = $event->getForm();
            $rate = (float) ($form->get('overtimeRate')->getData() ?? 0);
            $hours = (float) ($p->getOvertimeHours() ?? 0);
            if ($rate >= 0 && $hours >= 0) {
                $p->setOvertimeTotal(round($hours * $rate, 2));
            }

            $dj = $p->getDeductionsJson();
            if ($dj === null || trim((string) $dj) === '') {
                $p->setDeductionsJson('[]');
            }
            $bj = $p->getBonusesJson();
            if ($bj === null || trim((string) $bj) === '') {
                $p->setBonusesJson('[]');
            }
        });
    }

    private function monthLabel(int $m): string
    {
        return match ($m) {
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre',
            default => (string) $m,
        };
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payslip::class,
        ]);
    }
}
