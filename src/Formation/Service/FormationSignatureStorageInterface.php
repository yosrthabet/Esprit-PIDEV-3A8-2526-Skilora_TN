<?php

declare(strict_types=1);

namespace App\Formation\Service;

use App\Formation\Entity\Formation;

interface FormationSignatureStorageInterface
{
    public const STORED_SIGNATURE_BASENAME = 'signature.png';

    public function storePngBinary(Formation $formation, string $pngBinary): void;

    public function removeFilesForFormation(Formation $formation): void;

    public function getAbsolutePath(Formation $formation): ?string;
}
