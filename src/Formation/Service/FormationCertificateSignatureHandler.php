<?php

declare(strict_types=1);

namespace App\Formation\Service;

use App\Formation\Entity\Formation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class FormationCertificateSignatureHandler
{
    public function __construct(
        private readonly FormationSignatureStorageInterface $signatureStorage,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function handleSignatureFromRequest(Request $request, Formation $formation): void
    {
        $signatureData = trim($request->request->getString('signature_data'));
        $removeSignature = $request->request->getBoolean('remove_signature');

        if ($removeSignature && '' === $signatureData) {
            $this->signatureStorage->removeFilesForFormation($formation);
            $formation->setDirectorSignature(null);
            $this->entityManager->flush();
            return;
        }

        if ('' === $signatureData) {
            return;
        }

        $pngBinary = $this->decodeDataUrl($signatureData);
        $this->signatureStorage->storePngBinary($formation, $pngBinary);
        $formation->setDirectorSignature($signatureData);
        $this->entityManager->flush();
    }

    public function getSignatureDataUri(Formation $formation): ?string
    {
        if ($formation->getDirectorSignature()) {
            return $formation->getDirectorSignature();
        }

        $path = $this->signatureStorage->getAbsolutePath($formation);
        if (null === $path) {
            return null;
        }

        $binary = @file_get_contents($path);
        if (false === $binary || '' === $binary) {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($binary);
    }

    private function decodeDataUrl(string $dataUrl): string
    {
        $dataUrl = trim($dataUrl);
        if (!str_starts_with($dataUrl, 'data:image/png')) {
            throw new \InvalidArgumentException('Invalid signature payload.');
        }

        $comma = strpos($dataUrl, ',');
        if (false === $comma) {
            throw new \InvalidArgumentException('Invalid signature payload.');
        }

        $meta = substr($dataUrl, 0, $comma);
        if (!str_contains($meta, 'base64')) {
            throw new \InvalidArgumentException('Invalid signature payload.');
        }

        $b64 = preg_replace('/\s+/', '', substr($dataUrl, $comma + 1));
        if ($b64 === null) {
            throw new \InvalidArgumentException('Invalid signature payload.');
        }
        $binary = base64_decode($b64, true);
        if (false === $binary || '' === $binary) {
            throw new \InvalidArgumentException('Unable to decode signature payload.');
        }

        if ("\x89PNG\x0D\x0A\x1A\x0A" !== substr($binary, 0, 8)) {
            throw new \InvalidArgumentException('Decoded signature is not a PNG image.');
        }

        return $binary;
    }
}
