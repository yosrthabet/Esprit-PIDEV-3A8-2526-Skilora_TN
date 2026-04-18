<?php

namespace App\Form;

use App\Entity\MemberInvitation;
use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MemberInvitationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $me = $options['current_user'];
        $builder
            ->add('invitee', EntityType::class, [
                'class' => User::class,
                'choice_label' => fn (User $u) => $u->getUsername().' — '.$u->getFullName(),
                'query_builder' => function (EntityRepository $er) use ($me) {
                    return $er->createQueryBuilder('u')
                        ->where('u != :me')
                        ->setParameter('me', $me)
                        ->orderBy('u.username', 'ASC');
                },
                'label' => 'Membre à inviter',
                'placeholder' => 'Choisir un participant…',
                'required' => true,
            ])
            ->add('note', TextareaType::class, [
                'required' => false,
                'label' => 'Message d’accompagnement (optionnel)',
                'attr' => [
                    'rows' => 3,
                    'maxlength' => 500,
                    'class' => 'flex min-h-[72px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MemberInvitation::class,
            'current_user' => null,
        ]);
        $resolver->setAllowedTypes('current_user', [User::class]);
    }
}
