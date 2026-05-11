<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use App\Enum\Currency;
use App\Enum\ExperienceLevel;
use App\Enum\WorkType;
use App\Recruitment\Entity\JobOffer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<JobOffer> */
class JobOfferType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Job title', 'attr' => ['placeholder' => 'e.g. Full-Stack Developer']])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 6]])
            ->add('requirements', TextareaType::class, ['label' => 'Requirements', 'required' => false, 'attr' => ['rows' => 4]])
            ->add('skillsRequired', TextType::class, ['label' => 'Skills (comma-separated)', 'required' => false, 'attr' => ['placeholder' => 'PHP, Symfony, React']])
            ->add('benefits', TextareaType::class, ['label' => 'Benefits', 'required' => false, 'attr' => ['rows' => 3]])
            ->add('location', TextType::class, ['label' => 'Location', 'required' => false, 'attr' => ['placeholder' => 'Tunis, Remote, etc.']])
            ->add('workType', ChoiceType::class, [
                'label' => 'Work type',
                'required' => false,
                'placeholder' => 'Select...',
                'choices' => array_combine(
                    array_map(fn (WorkType $w) => ucfirst($w->value), WorkType::cases()),
                    WorkType::cases(),
                ),
            ])
            ->add('experienceLevel', ChoiceType::class, [
                'label' => 'Experience level',
                'required' => false,
                'placeholder' => 'Select...',
                'choices' => array_combine(
                    array_map(fn (ExperienceLevel $e) => ucfirst($e->value), ExperienceLevel::cases()),
                    ExperienceLevel::cases(),
                ),
            ])
            ->add('minSalary', MoneyType::class, ['label' => 'Min salary', 'currency' => false, 'required' => false])
            ->add('maxSalary', MoneyType::class, ['label' => 'Max salary', 'currency' => false, 'required' => false])
            ->add('currency', ChoiceType::class, [
                'label' => 'Currency',
                'choices' => array_combine(
                    array_map(fn (Currency $c) => $c->value, Currency::cases()),
                    Currency::cases(),
                ),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => JobOffer::class]);
    }
}
