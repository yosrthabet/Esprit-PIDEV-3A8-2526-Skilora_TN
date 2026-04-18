<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Formation;
use App\Form\Formation\FormationFormConfigurator;
use App\Validation\ValidationGroups;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formation catalogue metadata + certificate branding uploads (separated in {@see FormationFormConfigurator}).
 */
final class FormationType extends AbstractType
{
    public function __construct(
        private readonly FormationFormConfigurator $formationFormConfigurator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->formationFormConfigurator->configureMetadata($builder);
        $this->formationFormConfigurator->configureCertificateBranding($builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Formation::class,
            'attr' => ['novalidate' => 'novalidate'],
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'formation_edit',
            'show_signature_remove_checkbox' => false,
            'validation_groups' => static function (FormInterface $form): array {
                $data = $form->getData();
                if ($data instanceof Formation && null !== $data->getId()) {
                    return [ValidationGroups::FORMATION_UPDATE];
                }

                return [ValidationGroups::FORMATION_CREATE];
            },
        ]);

        $resolver->setAllowedTypes('show_signature_remove_checkbox', 'bool');
    }
}
