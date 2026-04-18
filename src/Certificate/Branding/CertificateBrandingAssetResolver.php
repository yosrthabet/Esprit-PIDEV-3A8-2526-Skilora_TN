<?php

declare(strict_types=1);

namespace App\Certificate\Branding;

use App\Entity\Certificate;

final class CertificateBrandingAssetResolver implements CertificateBrandingAssetResolverInterface
{
    public function __construct(
        private readonly FormationSignatureStorageInterface $formationSignatureStorage,
    ) {
    }

    public function getDirectorSignatureDataUri(Certificate $certificate): ?string
    {
        $formation = $certificate->getFormation();
        if (null === $formation) {
            return null;
        }

        $path = $this->formationSignatureStorage->getAbsolutePath($formation);
        if (null === $path || !is_readable($path)) {
            return null;
        }

        $bytes = @file_get_contents($path);
        if (false === $bytes || '' === $bytes) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode($bytes);
    }
}
