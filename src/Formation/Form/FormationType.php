<?php

declare(strict_types=1);

namespace App\Formation\Form;

use App\Formation\Entity\Formation;
use App\Formation\FormationLevel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<Formation> */
class FormationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['label' => 'Title', 'attr' => ['placeholder' => 'Formation title']])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 5]])
            ->add('category', TextType::class, ['label' => 'Category', 'attr' => ['placeholder' => 'e.g. Web Development']])
            ->add('level', ChoiceType::class, [
                'label' => 'Level',
                'choices' => array_combine(
                    array_map(fn (FormationLevel $l) => $l->labelFr(), FormationLevel::cases()),
                    FormationLevel::cases(),
                ),
            ])
            ->add('durationHours', IntegerType::class, ['label' => 'Duration (hours)', 'attr' => ['min' => 0]])
            ->add('priceAmount', MoneyType::class, ['label' => 'Price', 'currency' => 'TND', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Formation::class]);
    }
}
