<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Certificate;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Writer\WriterInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CertificateQrCodeService
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly BaseUrlResolver $baseUrlResolver,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getVerificationUrl(Certificate $certificate): string
    {
        $verificationId = $certificate->getVerificationId();
        if (null === $verificationId || '' === trim($verificationId)) {
            throw new \RuntimeException('Certificate verification ID is missing.');
        }

        $network = $this->baseUrlResolver->resolveLanBaseUrl();
        $path = $this->urlGenerator->generate('certificate_verify', [
            'verificationId' => $verificationId,
        ], UrlGeneratorInterface::ABSOLUTE_PATH);
        $baseUrl = $network['baseUrl'];
        $url = $baseUrl.$path;
        $this->assertPubliclyReachableUrl($url);

        if (('1' === ($_ENV['QR_DEBUG_DUMP'] ?? '0')) && \function_exists('dump')) {
            dump($url);
            exit;
        }

        $this->logger->debug('Generated certificate verification URL for QR code.', [
            'certificateId' => $certificate->getId(),
            'verificationId' => $verificationId,
            'detectedLanIp' => $network['ip'],
            'networkInterface' => $network['interface'],
            'ipSource' => $network['source'],
            'activePort' => $network['port'],
            'baseUrl' => $baseUrl,
            'url' => $url,
        ]);

        return $url;
    }

    public function generateDataUri(Certificate $certificate, int $size = 220): string
    {
        $writer = $this->createWriter();
        $result = Builder::create()
            ->writer($writer)
            ->data($this->getVerificationUrl($certificate))
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(max(80, $size))
            ->margin(8)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        return $result->getDataUri();
    }

    private function createWriter(): WriterInterface
    {
        // SVG avoids GD when Dompdf embeds the QR in PDFs (PNG paths require imagecreatefrompng).
        return new SvgWriter();
    }

    private function assertPubliclyReachableUrl(string $url): void
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (!\is_string($host) || '' === $host) {
            throw new \RuntimeException('Generated certificate verification URL has no host.');
        }

        if (\in_array(strtolower($host), ['localhost', '127.0.0.1', '0.0.0.0'], true)) {
            throw new \RuntimeException(sprintf(
                'Generated certificate verification URL host "%s" is not reachable from mobile devices. Configure APP_URL with a LAN IP or public domain.',
                $host
            ));
        }
    }
}
