<?php

declare(strict_types=1);

namespace App\Recruitment\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

/** @extends AbstractType<null> */
class JobApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('coverLetter', TextareaType::class, [
                'label' => 'Cover Letter',
                'required' => false,
                'attr' => ['rows' => 5, 'placeholder' => 'Why are you a great fit for this role?'],
            ])
            ->add('expectedSalary', TextType::class, [
                'label' => 'Expected Salary',
                'required' => false,
                'attr' => ['placeholder' => 'e.g. 3000 TND/month'],
            ])
            ->add('cv', FileType::class, [
                'label' => 'CV / Resume (PDF)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Please upload a valid PDF.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
