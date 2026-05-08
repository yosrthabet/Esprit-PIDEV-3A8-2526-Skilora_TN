<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\CvBuilder\CvBuilderData;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

final class CvPdfGeneratorService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $projectDir,
    ) {
    }

    public function generatePdfBinary(CvBuilderData $data): string
    {
        $template = $data->template === 'classic'
            ? 'recruitment/cv/pdf/classic.html.twig'
            : 'recruitment/cv/pdf/modern.html.twig';

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', [realpath($this->projectDir) ?: $this->projectDir]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($this->twig->render($template, ['cv' => $data]), 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
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
