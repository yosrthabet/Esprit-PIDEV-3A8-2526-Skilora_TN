<?php

declare(strict_types=1);

namespace App\Formation;

use App\Certificate\Branding\FormationSignatureStorageInterface;
use App\Entity\Formation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;

/**
 * Bridges {@see FormationType} certificate branding fields with storage (SRP: metadata persistence vs files).
 */
final class FormationCertificateSignatureFormHandler
{
    public function __construct(
        private readonly FormationSignatureStorageInterface $formationSignatureStorage,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function validateSubmittedSignature(FormInterface $form, Formation $formation): void
    {
        $removeRequested = $form->has('removeCertificateSignature')
            && true === $form->get('removeCertificateSignature')->getData();
        $signatureData = trim((string) $form->get('signatureData')->getData());
        $hasStoredSignature = null !== $formation->getCertificateSignatureFilename() && '' !== trim((string) $formation->getCertificateSignatureFilename());

        if ('' === $signatureData && !$hasStoredSignature && !$removeRequested) {
            $form->get('signatureData')->addError(new FormError('Veuillez dessiner une signature avant de soumettre le formulaire.'));
        }

        if ($removeRequested && '' === $signatureData && !$hasStoredSignature) {
            $form->get('signatureData')->addError(new FormError('Une signature est requise pour cette formation.'));
        }

        if ('' !== $signatureData) {
            try {
                $this->decodeDataUrl($signatureData);
            } catch (\InvalidArgumentException) {
                $form->get('signatureData')->addError(new FormError('Le format de la signature est invalide.'));
            }
        }
    }

    public function syncFromForm(FormInterface $form, Formation $formation): void
    {
        $removeRequested = $form->has('removeCertificateSignature')
            && true === $form->get('removeCertificateSignature')->getData();
        $signatureData = trim((string) $form->get('signatureData')->getData());

        if ('' !== $signatureData) {
            $this->formationSignatureStorage->storePngBinary($formation, $this->decodeDataUrl($signatureData));
            $this->entityManager->flush();

            return;
        }

        if ($removeRequested) {
            $this->formationSignatureStorage->removeFilesForFormation($formation);
            $this->entityManager->flush();
        }
    }

    private function decodeDataUrl(string $dataUrl): string
    {
        $dataUrl = trim($dataUrl);
        // Avoid running one giant regex on multi‑MB canvas exports (PCRE can hit backtrack limits → HTTP 500).
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
