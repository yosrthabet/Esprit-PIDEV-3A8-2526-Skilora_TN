<?php

namespace App\Form;

use App\Entity\MessageTicket;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MessageTicketType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('message', TextareaType::class, [
                'label' => 'Votre message',
                'attr' => ['rows' => 4],
            ])
            ->add('attachmentsJson', TextareaType::class, [
                'label' => 'Attachments (JSON)',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => '["url1","url2"]'],
            ])
            ->add('isInternal', CheckboxType::class, [
                'label' => 'Interne (admin only)',
                'required' => false,
            ]);

        if (!$options['is_admin']) {
            $builder->remove('isInternal');
            $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
                $data = $event->getData();
                if (\is_array($data)) {
                    $data['isInternal'] = false;
                    $event->setData($data);
                }
            });
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MessageTicket::class,
            'is_admin' => false,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
