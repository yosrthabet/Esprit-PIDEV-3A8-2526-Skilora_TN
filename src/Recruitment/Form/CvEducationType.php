<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/** @extends AbstractType<null> */
class CvEducationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('degree', TextType::class, ['label' => 'Degree / Diploma', 'required' => true])
            ->add('institution', TextType::class, ['label' => 'Institution', 'required' => true])
            ->add('year', TextType::class, ['label' => 'Year', 'required' => false, 'attr' => ['placeholder' => '2020-2024']]);
    }
}
