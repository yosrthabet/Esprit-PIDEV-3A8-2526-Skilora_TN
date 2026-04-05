<?php

namespace App\Form;

use App\Entity\CommunityPost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CommunityPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (!\is_array($data)) {
                return;
            }
            if (\array_key_exists('imageUrl', $data) && $data['imageUrl'] === '') {
                $data['imageUrl'] = null;
            }
            $event->setData($data);
        });

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
            ->add('imageUrl', UrlType::class, [
                'label' => 'Lien vers une image (optionnel)',
                'required' => false,
                'default_protocol' => 'https',
                'attr' => [
                    'placeholder' => 'https://exemple.com/image.jpg',
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
