<?php

declare(strict_types=1);

namespace App\Certificate\Branding;

use App\Entity\Formation;

/**
 * Persists certificate director signature images (PNG) outside the public web root with restrictive permissions.
 */
interface FormationSignatureStorageInterface
{
    public const STORED_SIGNATURE_BASENAME = 'signature.png';

    public function storePngBinary(Formation $formation, string $pngBinary): void;

    public function removeFilesForFormation(Formation $formation): void;

    /**
     * Deletes on-disk files when the formation row is removed (entity may still be managed).
     */
    public function deleteAllFilesForFormationId(int $formationId): void;

    public function getAbsolutePath(Formation $formation): ?string;
}
