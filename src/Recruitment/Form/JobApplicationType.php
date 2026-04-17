<?php

namespace App\Recruitment\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;

class JobApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('cv', FileType::class, [
                'label' => 'CV (PDF, Word ou image)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'application/pdf',
                            'application/x-pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Formats acceptés : PDF, Word (.doc, .docx), JPG, PNG, WebP.',
                    ),
                ],
                'attr' => [
                    'class' => 'block w-full cursor-pointer text-sm text-muted-foreground file:mr-4 file:cursor-pointer file:rounded-xl file:border-0 file:bg-primary file:px-5 file:py-2.5 file:text-sm file:font-semibold file:text-primary-foreground file:shadow-sm file:transition hover:file:bg-primary/90',
                ],
            ])
            ->add('useGeneratedCv', CheckboxType::class, [
                'label' => 'Utiliser mon CV généré depuis le formulaire',
                'mapped' => false,
                'required' => false,
            ])
            ->add('coverLetter', TextareaType::class, [
                'label' => 'Lettre de motivation (facultatif)',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'rows' => 8,
                    'class' => 'flex min-h-[140px] w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/50',
                    'placeholder' => 'Vous pouvez expliquer votre motivation…',
                ],
                'constraints' => [
                    new Length(max: 10000, maxMessage: 'La lettre ne peut pas dépasser {{ limit }} caractères.'),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
