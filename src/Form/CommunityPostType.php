<?php

namespace App\Form;

use App\Entity\CommunityPost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CommunityPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Quoi de neuf ?',
                'attr' => [
                    'rows' => 4,
                    'maxlength' => 5000,
                    'placeholder' => 'Partagez une idée, une annonce ou une actualité…',
                    'class' => 'flex min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Photo (optionnel)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Veuillez choisir une image valide (JPG, PNG, GIF, WebP).',
                        'maxSizeMessage' => 'L\'image ne doit pas dépasser 5 Mo.',
                    ]),
                ],
                'attr' => [
                    'accept' => 'image/jpeg,image/png,image/gif,image/webp',
                ],
            ])
            ->add('postType', ChoiceType::class, [
                'label' => 'Type de publication',
                'choices' => CommunityPost::POST_TYPES,
                'attr' => [
                    'class' => 'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommunityPost::class,
        ]);
    }
}
