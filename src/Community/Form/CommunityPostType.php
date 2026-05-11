<?php

declare(strict_types=1);

namespace App\Community\Form;

use App\Community\Entity\CommunityPost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<CommunityPost> */
class CommunityPostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                'label' => 'Content',
                'attr' => ['rows' => 8, 'placeholder' => 'Share an update, question, opportunity, or learning note...'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CommunityPost::class]);
    }
}
