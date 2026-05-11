<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/** @extends AbstractType<null> */
class CvExperienceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jobTitle', TextType::class, ['label' => 'Job Title', 'required' => true])
            ->add('company', TextType::class, ['label' => 'Company', 'required' => true])
            ->add('duration', TextType::class, ['label' => 'Duration', 'required' => false, 'attr' => ['placeholder' => 'Jan 2022 - Present']])
            ->add('description', TextareaType::class, ['label' => 'Description', 'required' => false, 'attr' => ['rows' => 3]]);
    }
}
