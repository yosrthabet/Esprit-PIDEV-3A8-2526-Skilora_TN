<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Certificate;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;

final class CertificatePdfGenerator
{
    public function generate(Certificate $certificate): string
    {
        $user = $certificate->getUser();
        $formation = $certificate->getFormation();
        if (!$user instanceof User || null === $formation) {
            throw new \InvalidArgumentException('Certificate must have user and formation.');
        }

        $name = $user->getFullName();
        $title = $formation->getTitle();
        $date = $certificate->getIssuedAt()?->format('d F Y') ?? (new \DateTimeImmutable())->format('d F Y');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <style>
    @page { margin: 0; }
    body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 48px; background: #f8fafc; color: #0f172a; }
    .frame { border: 4px solid #4f46e5; border-radius: 8px; padding: 56px 48px; min-height: 520px; text-align: center; background: #fff; }
    h1 { font-size: 28px; letter-spacing: 0.15em; text-transform: uppercase; color: #312e81; margin: 0 0 32px; }
    .subtitle { font-size: 14px; color: #64748b; margin-bottom: 24px; }
    .name { font-size: 36px; font-weight: bold; color: #0f172a; margin: 24px 0; line-height: 1.2; }
    .course { font-size: 20px; color: #4338ca; margin: 16px 0 32px; }
    .date { font-size: 14px; color: #64748b; margin-top: 40px; }
    .logo { font-size: 18px; font-weight: bold; color: #6366f1; margin-top: 48px; }
  </style>
</head>
<body>
  <div class="frame">
    <h1>Certificate of Completion</h1>
    <p class="subtitle">This certifies that</p>
    <p class="name">{$this->escape($name)}</p>
    <p class="subtitle">has successfully completed</p>
    <p class="course">{$this->escape($title)}</p>
    <p class="date">Issued on {$this->escape($date)}</p>
    <p class="logo">Skilora Talent</p>
  </div>
</body>
</html>
HTML;

        $options = new Options();
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    private function escape(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
