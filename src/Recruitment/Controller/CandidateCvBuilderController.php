<?php

declare(strict_types=1);

namespace App\Recruitment\Controller;

use App\Controller\AppController;
use App\Recruitment\CvBuilder\CvBuilderData;
use App\Recruitment\Service\CvPdfGeneratorService;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class CandidateCvBuilderController extends AppController
{
    private const MAX_PHOTO_BYTES = 2_097_152;
    private const PHOTO_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private readonly CvPdfGeneratorService $cvPdfGeneratorService,
        private readonly string $cvUploadDir,
    ) {
    }

    #[Route('/mon-espace/cv/generateur', name: 'app_candidate_cv_builder', methods: ['GET', 'POST'])]
    public function builder(Request $request): Response
    {
        $user = $this->getAppUser();
        if (strtoupper($user->getRole() ?? '') === 'EMPLOYER') {
            throw $this->createAccessDeniedException('Employers cannot use the candidate CV builder.');
        }

        $defaults = [
            'full_name' => $user->getFullName() ?? $user->getUsername() ?? '',
            'professional_title' => '',
            'email' => $user->getEmail() ?? '',
            'phone' => '',
            'address' => '',
            'professional_summary' => '',
            'skills' => '',
            'languages' => '',
            'template' => 'modern',
            'education' => [['degree' => '', 'institution' => '', 'year' => '']],
            'experience' => [['jobTitle' => '', 'company' => '', 'duration' => '', 'description' => '']],
        ];
        $data = $request->isMethod('POST') ? $this->payloadFromRequest($request) : $defaults;
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('cv_builder', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }

            $errors = $this->validatePayload($data, $request->files->get('photo'));
            if ($errors === []) {
                try {
                    $cvData = $this->buildCvData($data, $request->files->get('photo'));
                    if ($request->request->getString('intent') === 'save') {
                        $relativePath = $this->cvPdfGeneratorService->savePdfForUser($cvData, $user, $this->cvUploadDir);
                        $request->getSession()->set('candidate_generated_cv_relpath', $relativePath);
                        $this->addFlash('success', 'CV generated and saved for future applications.');

                        return $this->redirectToRoute('app_candidate_cv_builder');
                    }

                    $safeName = preg_replace('/[^a-z0-9]+/i', '_', strtolower($cvData->fullName)) ?: 'cv';

                    return new Response($this->cvPdfGeneratorService->generatePdfBinary($cvData), Response::HTTP_OK, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'attachment; filename="cv_' . trim($safeName, '_') . '.pdf"',
                    ]);
                } catch (\RuntimeException $exception) {
                    $errors[] = $exception->getMessage();
                }
            }
        }

        return $this->render('recruitment/cv/builder.html.twig', [
            'data' => $data,
            'errors' => $errors,
            'saved_cv_relpath' => $request->getSession()->get('candidate_generated_cv_relpath'),
        ]);
    }

    /** @return array<string, mixed> */
    private function payloadFromRequest(Request $request): array
    {
        return [
            'full_name' => trim($request->request->getString('full_name')),
            'professional_title' => trim($request->request->getString('professional_title')),
            'email' => trim($request->request->getString('email')),
            'phone' => trim($request->request->getString('phone')),
            'address' => trim($request->request->getString('address')),
            'professional_summary' => trim($request->request->getString('professional_summary')),
            'skills' => trim($request->request->getString('skills')),
            'languages' => trim($request->request->getString('languages')),
            'template' => $request->request->getString('template') === 'classic' ? 'classic' : 'modern',
            'education' => $this->normalizeEducation($request->request->all('education')),
            'experience' => $this->normalizeExperience($request->request->all('experience')),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    private function validatePayload(array $payload, mixed $photo): array
    {
        $errors = [];
        if (($payload['full_name'] ?? '') === '') {
            $errors[] = 'Full name is required.';
        }
        if (($payload['professional_title'] ?? '') === '') {
            $errors[] = 'Professional title is required.';
        }
        if (!filter_var($payload['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }
        if (($payload['professional_summary'] ?? '') === '') {
            $errors[] = 'Professional summary is required.';
        }
        if (($payload['skills'] ?? '') === '') {
            $errors[] = 'At least one skill is required.';
        }
        if ($photo instanceof UploadedFile) {
            if (!$photo->isValid()) {
                $errors[] = 'Profile photo upload failed.';
            } elseif (($photo->getSize() ?? 0) > self::MAX_PHOTO_BYTES) {
                $errors[] = 'Profile photo must be 2 MB or smaller.';
            } elseif (!in_array($photo->getMimeType() ?? '', self::PHOTO_MIME_TYPES, true)) {
                $errors[] = 'Profile photo must be JPEG, PNG, or WebP.';
            }
        }

        return $errors;
    }

    /** @param array<string, mixed> $payload */
    private function buildCvData(array $payload, mixed $photo): CvBuilderData
    {
        return new CvBuilderData(
            $this->stringValue($payload['full_name'] ?? ''),
            $this->stringValue($payload['professional_title'] ?? ''),
            $this->stringValue($payload['email'] ?? ''),
            $this->nullIfBlank($payload['phone'] ?? null),
            $this->nullIfBlank($payload['address'] ?? null),
            $this->stringValue($payload['professional_summary'] ?? ''),
            $this->normalizeEducation(is_array($payload['education'] ?? null) ? $payload['education'] : []),
            $this->normalizeExperience(is_array($payload['experience'] ?? null) ? $payload['experience'] : []),
            $this->stringValue($payload['skills'] ?? ''),
            $this->nullIfBlank($payload['languages'] ?? null),
            $this->stringValue($payload['template'] ?? 'modern'),
            $this->photoToDataUri($photo),
        );
    }

    /**
     * @param array<mixed> $rows
     * @return list<array{degree: string, institution: string, year: string}>
     */
    private function normalizeEducation(array $rows): array
    {
        $education = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $entry = [
                'degree' => $this->stringValue($row['degree'] ?? ''),
                'institution' => $this->stringValue($row['institution'] ?? ''),
                'year' => $this->stringValue($row['year'] ?? ''),
            ];
            if (implode('', $entry) !== '') {
                $education[] = $entry;
            }
        }

        return $education;
    }

    /**
     * @param array<mixed> $rows
     * @return list<array{jobTitle: string, company: string, duration: string, description: string}>
     */
    private function normalizeExperience(array $rows): array
    {
        $experience = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $entry = [
                'jobTitle' => $this->stringValue($row['jobTitle'] ?? ''),
                'company' => $this->stringValue($row['company'] ?? ''),
                'duration' => $this->stringValue($row['duration'] ?? ''),
                'description' => $this->stringValue($row['description'] ?? ''),
            ];
            if (implode('', $entry) !== '') {
                $experience[] = $entry;
            }
        }

        return $experience;
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = $this->stringValue($value);

        return $value !== '' ? $value : null;
    }

    private function stringValue(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return trim((string) $value);
        }

        return '';
    }

    private function photoToDataUri(mixed $photo): ?string
    {
        if (!$photo instanceof UploadedFile || !$photo->isValid()) {
            return null;
        }

        $raw = file_get_contents($photo->getPathname());
        if ($raw === false || $raw === '') {
            return null;
        }

        return 'data:' . ($photo->getMimeType() ?: 'application/octet-stream') . ';base64,' . base64_encode($raw);
    }
}
