<?php

namespace App\Form;

use App\Entity\BlogArticle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BlogArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
                'attr' => [
                    'maxlength' => 255,
                    'placeholder' => 'Titre de l\'article…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu',
                'attr' => [
                    'rows' => 12,
                    'maxlength' => 50000,
                    'placeholder' => 'Rédigez votre article ici…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Résumé (optionnel)',
                'required' => false,
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 500,
                    'placeholder' => 'Un court résumé de votre article…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('coverImageUrl', UrlType::class, [
                'label' => 'Image de couverture (URL, optionnel)',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://exemple.com/image.jpg',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('category', TextType::class, [
                'label' => 'Catégorie (optionnel)',
                'required' => false,
                'attr' => [
                    'maxlength' => 100,
                    'placeholder' => 'Technologie, Carrière, Formation…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('tags', TextType::class, [
                'label' => 'Tags (séparés par des virgules)',
                'required' => false,
                'attr' => [
                    'maxlength' => 500,
                    'placeholder' => 'symfony, php, web…',
                    'class' => 'mt-1 block w-full rounded-lg border border-border bg-background px-4 py-2.5 text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-1 focus:ring-primary',
                ],
            ])
            ->add('isPublished', CheckboxType::class, [
                'label' => 'Publier immédiatement',
                'required' => false,
                'attr' => [
                    'class' => 'h-4 w-4 rounded border-border text-primary focus:ring-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BlogArticle::class,
        ]);
    }
}
