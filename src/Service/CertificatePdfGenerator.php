<?php

declare(strict_types=1);

namespace App\Service;

use App\Certificate\Branding\CertificateBrandingAssetResolverInterface;
use App\Entity\Certificate;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class CertificatePdfGenerator
{
    public function __construct(
        private readonly CertificateQrCodeService $certificateQrCodeService,
        private readonly CertificateBrandingAssetResolverInterface $certificateBrandingAssetResolver,
        private readonly Environment $twig,
    ) {
    }

    public function generate(Certificate $certificate): string
    {
        $user = $certificate->getUser();
        $formation = $certificate->getFormation();
        if (!$user instanceof User || null === $formation) {
            throw new \InvalidArgumentException('Certificate must have user and formation.');
        }

        $verificationUrl = $this->certificateQrCodeService->getVerificationUrl($certificate);
        $verificationHost = parse_url($verificationUrl, PHP_URL_HOST);
        if (!\is_string($verificationHost) || '' === $verificationHost) {
            $verificationHost = 'localhost';
        }
        $qrCodeDataUri = $this->certificateQrCodeService->generateDataUri($certificate, 160);
        $formationDirectorSignatureDataUri = $this->certificateBrandingAssetResolver->getDirectorSignatureDataUri($certificate);
        // Dompdf embeds PNG images via GD (imagecreatefrompng). Without ext-gd, skip the signature image so PDF still generates.
        if (!\extension_loaded('gd')) {
            $formationDirectorSignatureDataUri = null;
        }
        $html = $this->twig->render('certificate/pdf.html.twig', [
            'certificate' => $certificate,
            'verificationUrl' => $verificationUrl,
            'verificationHost' => $verificationHost,
            'qrCodeDataUri' => $qrCodeDataUri,
            'formationDirectorSignatureDataUri' => $formationDirectorSignatureDataUri,
        ]);

        $options = new Options();
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }
}
