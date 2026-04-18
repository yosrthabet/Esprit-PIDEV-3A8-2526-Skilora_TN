<?php

declare(strict_types=1);

namespace App\Certificate\Branding;

use App\Entity\Formation;

/**
 * Filesystem-backed storage for formation certificate branding assets (SOLID: single responsibility).
 */
final class FormationSignatureStorage implements FormationSignatureStorageInterface
{
    public function __construct(
        private readonly string $signaturesRoot,
    ) {
    }

    public function storePngBinary(Formation $formation, string $pngBinary): void
    {
        $id = $formation->getId();
        if (null === $id) {
            throw new \LogicException('Formation must be persisted before storing a signature file.');
        }

        $dir = $this->directoryForFormationId($id);
        if (!is_dir($dir) && !@mkdir($dir, 0770, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('Cannot create signature directory: %s', $dir));
        }

        $target = $dir.'/'.self::STORED_SIGNATURE_BASENAME;

        $this->removeIfExists($target);
        if ('' === $pngBinary) {
            throw new \InvalidArgumentException('Signature PNG binary cannot be empty.');
        }
        if (false === @file_put_contents($target, $pngBinary, LOCK_EX)) {
            throw new \RuntimeException(sprintf('Cannot write signature file: %s', $target));
        }

        if (is_file($target)) {
            @chmod($target, 0600);
        }

        $formation->setCertificateSignatureFilename(self::STORED_SIGNATURE_BASENAME);
    }

    public function removeFilesForFormation(Formation $formation): void
    {
        $id = $formation->getId();
        if (null === $id) {
            $formation->setCertificateSignatureFilename(null);

            return;
        }

        $dir = $this->directoryForFormationId($id);
        if (is_dir($dir)) {
            $this->deleteDirectoryContents($dir);
            @rmdir($dir);
        }

        $formation->setCertificateSignatureFilename(null);
    }

    public function getAbsolutePath(Formation $formation): ?string
    {
        $id = $formation->getId();
        $name = $formation->getCertificateSignatureFilename();
        if (null === $id || null === $name || '' === $name) {
            return null;
        }

        $path = $this->directoryForFormationId($id).'/'.$name;

        return is_file($path) ? $path : null;
    }

    public function deleteAllFilesForFormationId(int $formationId): void
    {
        $dir = $this->directoryForFormationId($formationId);
        if (!is_dir($dir)) {
            return;
        }

        $this->deleteDirectoryContents($dir);
        @rmdir($dir);
    }

    private function directoryForFormationId(int $id): string
    {
        return $this->signaturesRoot.'/'.$id;
    }

    private function removeIfExists(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function deleteDirectoryContents(string $dir): void
    {
        $items = @scandir($dir);
        if (false === $items) {
            return;
        }
        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }
            $path = $dir.'/'.$item;
            if (is_file($path)) {
                @unlink($path);
            } elseif (is_dir($path)) {
                $this->deleteDirectoryContents($path);
                @rmdir($path);
            }
        }
    }
}
