<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\CvBuilder\CvBuilderData;
use Psr\Log\LoggerInterface;
use Twig\Environment;

final class CvPdfGeneratorService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $projectDir,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function generatePdfBinary(CvBuilderData $data): string
    {
        $template = $data->template === 'classic'
            ? 'recruitment/cv/pdf/classic.tex.twig'
            : 'recruitment/cv/pdf/modern.tex.twig';

        $latex = $this->twig->render($template, ['cv' => $data]);

        return $this->compileLaTeX($latex);
    }

    private function compileLaTeX(string $latex): string
    {
        $tmpDir = sys_get_temp_dir() . '/skilora_cv_' . bin2hex(random_bytes(8));
        if (!mkdir($tmpDir, 0755, true)) {
            throw new \RuntimeException('Cannot create temp directory for LaTeX compilation.');
        }

        $texFile = $tmpDir . '/cv.tex';
        file_put_contents($texFile, $latex);

        $cmd = sprintf(
            'cd %s && /usr/bin/pdflatex -interaction=nonstopmode -halt-on-error cv.tex 2>&1',
            escapeshellarg($tmpDir)
        );

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        $pdfFile = $tmpDir . '/cv.pdf';
        if (!file_exists($pdfFile)) {
            $log = implode("\n", array_slice($output, -30));
            $this->logger->error('LaTeX compilation failed', ['output' => $log, 'exit_code' => $exitCode, 'tmpDir' => $tmpDir]);

            @copy($texFile, '/tmp/skilora_cv_last_failed.tex');
            file_put_contents('/tmp/skilora_cv_last_failed.log', implode("\n", $output));

            $this->cleanupDir($tmpDir);
            throw new \RuntimeException('CV generation failed. Please check your input for special characters and try again.');
        }

        $pdf = file_get_contents($pdfFile);
        $this->cleanupDir($tmpDir);

        if ($pdf === false || $pdf === '') {
            throw new \RuntimeException('Generated PDF is empty.');
        }

        return $pdf;
    }

    private function cleanupDir(string $dir): void
    {
        $files = glob($dir . '/*') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }

    /**
     * @return string Relative path under the private CV upload directory.
     */
    public function savePdfForUser(CvBuilderData $data, User $user, string $cvUploadDir): string
    {
        $userId = $user->getId();
        if ($userId === null) {
            throw new \InvalidArgumentException('User must be persisted before saving a generated CV.');
        }

        $subDir = 'generated/' . (new \DateTimeImmutable())->format('Y/m');
        $targetDir = rtrim($cvUploadDir, '/\\') . '/' . $subDir;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create generated CV directory.');
        }

        $filename = sprintf('u%d_cv_%s.pdf', $userId, bin2hex(random_bytes(8)));
        $fullPath = $targetDir . '/' . $filename;
        if (file_put_contents($fullPath, $this->generatePdfBinary($data)) === false) {
            throw new \RuntimeException('Unable to save generated CV.');
        }

        return $subDir . '/' . $filename;
    }
}
