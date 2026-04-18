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
        $gradeMetrics = [
            'score' => null,
            'maxScore' => null,
            'label' => 'N/A',
        ];
        $qrCodeDataUri = $this->certificateQrCodeService->generateDataUri($certificate, 80);
        $formationDirectorSignatureDataUri = $this->certificateBrandingAssetResolver->getDirectorSignatureDataUri($certificate);
        $html = $this->twig->render('certificate/pdf.html.twig', [
            'certificate' => $certificate,
            'verificationUrl' => $verificationUrl,
            'qrCodeDataUri' => $qrCodeDataUri,
            'gradeMetrics' => $gradeMetrics,
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
