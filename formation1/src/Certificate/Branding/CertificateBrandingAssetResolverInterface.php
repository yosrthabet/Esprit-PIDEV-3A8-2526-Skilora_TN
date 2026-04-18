<?php

declare(strict_types=1);

namespace App\Certificate\Branding;

use App\Entity\Certificate;

/**
 * Resolves certificate branding assets (e.g. director signature image) for PDF/SVG rendering.
 */
interface CertificateBrandingAssetResolverInterface
{
    /**
     * Returns a data URI suitable for embedding in Dompdf/SVG (PNG), or null if none.
     */
    public function getDirectorSignatureDataUri(Certificate $certificate): ?string;
}
