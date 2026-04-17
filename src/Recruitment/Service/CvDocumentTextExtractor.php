<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use Smalot\PdfParser\Parser;

/**
 * Extrait le texte brut d’un CV (PDF, TXT, DOCX) pour le calcul de correspondance offre / CV.
 */
final class CvDocumentTextExtractor
{
    public function __construct(
        private readonly OcrApiTextExtractor $ocrApiTextExtractor,
    ) {
    }

    public function extractFromAbsolutePath(string $absolutePath): ?string
    {
        if (!is_file($absolutePath) || !is_readable($absolutePath)) {
            return null;
        }

        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if ($ext === 'webapp') {
            // Some browsers/users upload WebP images with a wrong extension like ".webapp".
            $ext = 'webp';
        }

        $fromExt = match ($ext) {
            'pdf' => $this->extractPdf($absolutePath),
            'txt' => $this->extractPlain($absolutePath),
            'docx' => $this->extractDocx($absolutePath),
            'jpg', 'jpeg', 'png', 'webp' => $this->extractImageViaOcr($absolutePath),
            default => null,
        };
        if ($fromExt !== null) {
            return $fromExt;
        }

        return $this->extractByMimeTypeFallback($absolutePath);
    }

    private function extractPdf(string $path): ?string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($path);
            $text = $pdf->getText();
            $t = trim($text);

            return $t !== '' ? $t : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractPlain(string $path): ?string
    {
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }
        $t = trim($raw);

        return $t !== '' ? $t : null;
    }

    private function extractDocx(string $path): ?string
    {
        if (!\extension_loaded('zip') || !class_exists(\ZipArchive::class)) {
            return null;
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return null;
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($xml === false) {
            return null;
        }

        $text = strip_tags(str_replace(['</w:p>', '</w:tab>'], ["\n", ' '], $xml));
        $t = trim(html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8'));

        return $t !== '' ? $t : null;
    }

    private function extractImageViaOcr(string $path): ?string
    {
        return $this->ocrApiTextExtractor->extractFromImagePath($path);
    }

    private function extractByMimeTypeFallback(string $path): ?string
    {
        $mime = $this->detectMimeType($path);
        if ($mime === null) {
            return null;
        }

        return match ($mime) {
            'application/pdf' => $this->extractPdf($path),
            'text/plain' => $this->extractPlain($path),
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => $this->extractDocx($path),
            'image/jpeg', 'image/png', 'image/webp' => $this->extractImageViaOcr($path),
            default => null,
        };
    }

    private function detectMimeType(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi !== false) {
                $mime = @finfo_file($fi, $path);
                @finfo_close($fi);
                if (\is_string($mime) && $mime !== '') {
                    return strtolower(trim($mime));
                }
            }
        }

        $mime = @mime_content_type($path);
        if (\is_string($mime) && $mime !== '') {
            return strtolower(trim($mime));
        }

        return null;
    }
}
