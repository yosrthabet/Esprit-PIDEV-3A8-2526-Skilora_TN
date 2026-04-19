<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PostCommentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'rows' => 2,
                    'maxlength' => 2000,
                    'placeholder' => 'Écrire un commentaire…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
                'constraints' => [
                    new NotBlank(message: 'Le commentaire ne peut pas être vide.'),
                    new Length(max: 2000),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
