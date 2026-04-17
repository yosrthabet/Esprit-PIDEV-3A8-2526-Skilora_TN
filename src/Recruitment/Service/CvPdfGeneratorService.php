<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\CvBuilder\CvBuilderData;
use Dompdf\Dompdf;
use Dompdf\Options;
use Twig\Environment;

/**
 * Rendu HTML → PDF (Dompdf), uniquement à partir des données saisies par l’utilisateur.
 */
final class CvPdfGeneratorService
{
    public function __construct(
        private readonly Environment $twig,
        private readonly string $projectDir,
    ) {
    }

    public function generatePdfBinary(CvBuilderData $data): string
    {
        $tpl = $data->template === 'classic'
            ? 'recrutement/cv/pdf/classic.html.twig'
            : 'recrutement/cv/pdf/modern.html.twig';

        $html = $this->twig->render($tpl, ['cv' => $data]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', [realpath($this->projectDir) ?: $this->projectDir]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        return $dompdf->output();
    }

    /**
     * Enregistre le PDF sous {@code var/uploads/cvs/YYYY/MM/} pour réutilisation à la candidature.
     *
     * @return string chemin relatif (ex. 2026/04/u5_cv_generated_ab12cd.pdf)
     */
    public function savePdfForUser(CvBuilderData $data, User $user, string $cvUploadDir): string
    {
        $uid = $user->getId();
        if ($uid === null) {
            throw new \InvalidArgumentException('Utilisateur sans identifiant.');
        }

        $binary = $this->generatePdfBinary($data);
        $subDir = (new \DateTimeImmutable())->format('Y/m');
        $targetDir = rtrim($cvUploadDir, '/\\').\DIRECTORY_SEPARATOR.str_replace('/', \DIRECTORY_SEPARATOR, $subDir);
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Impossible de créer le répertoire d’enregistrement du CV.');
        }

        $hash = bin2hex(random_bytes(8));
        $filename = 'u'.$uid.'_cv_generated_'.$hash.'.pdf';
        $full = $targetDir.\DIRECTORY_SEPARATOR.$filename;
        if (file_put_contents($full, $binary) === false) {
            throw new \RuntimeException('Impossible d’enregistrer le fichier PDF.');
        }

        return $subDir.'/'.$filename;
    }
}
