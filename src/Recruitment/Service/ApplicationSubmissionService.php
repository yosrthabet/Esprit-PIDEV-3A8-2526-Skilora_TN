<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\User;
use App\Recruitment\Entity\Application;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Repository\ApplicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ApplicationSubmissionService
{
    private const MAX_BYTES = 5_242_880;
    private const MIME_EXTENSIONS = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly ApplicationRepository $applicationRepository,
        private readonly JobMatchService $jobMatchService,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $cvUploadDir,
    ) {
    }

    public function submit(User $candidate, JobOffer $jobOffer, ?UploadedFile $cv, ?string $coverLetter): Application
    {
        if (strtoupper($candidate->getRole() ?? '') === 'EMPLOYER') {
            throw new \RuntimeException('Employers cannot apply to jobs.');
        }
        $existing = $this->applicationRepository->findOneBy(['candidate' => $candidate, 'jobOffer' => $jobOffer]);
        if ($existing !== null) {
            return $existing;
        }
        if (!$cv instanceof UploadedFile) {
            throw new \RuntimeException('A CV file is required.');
        }

        $relativePath = $this->storeCv($cv);
        return $this->createApplication($candidate, $jobOffer, $relativePath, $coverLetter);
    }

    public function submitUsingExistingCvPath(User $candidate, JobOffer $jobOffer, string $relativeCvPath, ?string $coverLetter): Application
    {
        if (strtoupper($candidate->getRole() ?? '') === 'EMPLOYER') {
            throw new \RuntimeException('Employers cannot apply to jobs.');
        }
        $existing = $this->applicationRepository->findOneBy(['candidate' => $candidate, 'jobOffer' => $jobOffer]);
        if ($existing !== null) {
            return $existing;
        }

        $relativePath = $this->normalizeExistingCvPath($relativeCvPath);

        return $this->createApplication($candidate, $jobOffer, $relativePath, $coverLetter);
    }

    private function createApplication(User $candidate, JobOffer $jobOffer, string $relativePath, ?string $coverLetter): Application
    {
        $match = $this->jobMatchService->score($candidate, $jobOffer);
        $application = (new Application())
            ->setCandidate($candidate)
            ->setJobOffer($jobOffer)
            ->setCvPath($relativePath)
            ->setCoverLetter(trim($coverLetter ?? '') ?: null)
            ->setMatchScore($match['score'])
            ->setMatchReasons($match['reasons']);

        $jobOffer->incrementApplications();
        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $application;
    }

    private function normalizeExistingCvPath(string $relativeCvPath): string
    {
        $relativeCvPath = trim(str_replace('\\', '/', $relativeCvPath));
        if ($relativeCvPath === '' || str_contains($relativeCvPath, "\0") || str_starts_with($relativeCvPath, '/')) {
            throw new \RuntimeException('Saved CV path is invalid.');
        }

        $parts = [];
        foreach (explode('/', $relativeCvPath) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                throw new \RuntimeException('Saved CV path cannot contain path traversal.');
            }
            $parts[] = $part;
        }
        if ($parts === []) {
            throw new \RuntimeException('Saved CV path is invalid.');
        }

        $normalized = implode('/', $parts);
        $baseDir = realpath($this->cvUploadDir);
        $fullPath = rtrim($this->cvUploadDir, '/\\') . '/' . $normalized;
        $resolvedPath = realpath($fullPath);
        if ($baseDir === false || $resolvedPath === false || !is_file($resolvedPath) || !is_readable($resolvedPath)) {
            throw new \RuntimeException('Saved generated CV is not available.');
        }

        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($resolvedPath, $baseDir)) {
            throw new \RuntimeException('Saved CV path must stay inside the CV upload directory.');
        }

        return $normalized;
    }

    private function storeCv(UploadedFile $file): string
    {
        if (!$file->isValid()) {
            throw new \RuntimeException('The uploaded CV is not valid.');
        }
        if ($file->getSize() !== null && $file->getSize() > self::MAX_BYTES) {
            throw new \RuntimeException('CV file must be 5 MB or smaller.');
        }

        $mime = $file->getMimeType() ?? '';
        $extension = self::MIME_EXTENSIONS[$mime] ?? strtolower($file->guessExtension() ?? $file->getClientOriginalExtension());
        if (!in_array($extension, self::MIME_EXTENSIONS, true)) {
            throw new \RuntimeException('CV must be a PDF, Word document, or image file.');
        }

        $month = date('Y/m');
        $targetDir = rtrim($this->cvUploadDir, '/\\') . '/' . $month;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create CV upload directory.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $file->move($targetDir, $filename);

        return $month . '/' . $filename;
    }
}
