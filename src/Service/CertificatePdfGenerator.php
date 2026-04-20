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
        if (!\extension_loaded('gd')) {
            $formationDirectorSignatureDataUri = null;
        }

        // Render the SVG certificate from Twig
        $svgHtml = $this->twig->render('certificate/_certificate_core.html.twig', [
            'certificate' => $certificate,
            'qrCodeDataUri' => null,
            'formationDirectorSignatureDataUri' => $formationDirectorSignatureDataUri,
            'forPdf' => true,
        ]);

        // Convert SVG → PNG via rsvg-convert for pixel-perfect rendering
        $pngDataUri = $this->convertSvgToPngDataUri($svgHtml);

        $html = $this->twig->render('certificate/pdf.html.twig', [
            'certificate' => $certificate,
            'verificationUrl' => $verificationUrl,
            'verificationHost' => $verificationHost,
            'qrCodeDataUri' => $qrCodeDataUri,
            'certImageDataUri' => $pngDataUri,
            'formationDirectorSignatureDataUri' => $formationDirectorSignatureDataUri,
        ]);

        $options = new Options();
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsRemoteEnabled(true);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    private function convertSvgToPngDataUri(string $svgContent): string
    {
        $tmpSvg = tempnam(sys_get_temp_dir(), 'cert_') . '.svg';
        $tmpPng = tempnam(sys_get_temp_dir(), 'cert_') . '.png';

        try {
            file_put_contents($tmpSvg, $svgContent);

            // rsvg-convert at 2x resolution (2246×1588) for crisp A4 landscape output
            $cmd = sprintf(
                'rsvg-convert -w 2246 -h 1588 -o %s %s 2>&1',
                escapeshellarg($tmpPng),
                escapeshellarg($tmpSvg)
            );
            exec($cmd, $output, $exitCode);

            if (0 !== $exitCode || !file_exists($tmpPng) || 0 === filesize($tmpPng)) {
                throw new \RuntimeException('rsvg-convert failed: ' . implode("\n", $output));
            }

            $pngData = file_get_contents($tmpPng);

            return 'data:image/png;base64,' . base64_encode($pngData);
        } finally {
            @unlink($tmpSvg);
            @unlink($tmpPng);
        }
    }
}
