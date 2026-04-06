<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class DmStartConversationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User[] $friends */
        $friends = $options['friends'];
        $builder
            ->add('recipient', EntityType::class, [
                'class' => User::class,
                'mapped' => false,
                'choices' => $friends,
                'choice_label' => fn (User $u) => $u->getUsername().' — '.$u->getFullName(),
                'label' => 'Ami',
                'placeholder' => $friends === [] ? 'Acceptez des invitations pour discuter' : 'Choisir un ami…',
                'required' => true,
            ])
            ->add('body', TextareaType::class, [
                'mapped' => false,
                'label' => 'Premier message',
                'constraints' => [
                    new Assert\NotBlank(message: 'Le message ne peut pas être vide.'),
                    new Assert\Length(min: 1, max: 4000, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 4000,
                    'class' => 'flex min-h-[72px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_token_id' => 'dm_start_conversation',
            'current_user' => null,
            'friends' => [],
        ]);
        $resolver->setAllowedTypes('current_user', [User::class]);
        $resolver->setAllowedTypes('friends', ['array']);
    }
}
