<?php

declare(strict_types=1);

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\CvBuilder\CvBuilderData;
use App\Recruitment\Form\CvBuilderType;
use App\Recruitment\Service\CvPdfGeneratorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-espace/cv', name: 'app_candidate_cv_')]
#[IsGranted('ROLE_USER')]
final class CandidateCvBuilderController extends AbstractController
{
    public function __construct(
        private readonly CvPdfGeneratorService $cvPdfGeneratorService,
        private readonly string $cvUploadDir,
    ) {
    }

    #[Route('/generateur', name: 'builder', methods: ['GET', 'POST'])]
    public function builder(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        if (\in_array('ROLE_EMPLOYER', $user->getRoles(), true)) {
            return $this->redirectToRoute('app_employer_dashboard');
        }

        $form = $this->createForm(CvBuilderType::class, [
            'fullName' => $user->getFullName() ?? '',
            'email' => $user->getEmail() ?? '',
            'template' => 'modern',
            'education' => [['degree' => '', 'institution' => '', 'year' => '']],
            'experience' => [['jobTitle' => '', 'company' => '', 'duration' => '', 'description' => '']],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();
            if (!\is_array($payload)) {
                $form->addError(new FormError('Données de formulaire invalides.'));

                return $this->render('recrutement/cv/builder.html.twig', ['form' => $form]);
            }

            $data = $this->buildCvDataFromPayload($payload, $form->get('photo')->getData());
            if ($form->get('download')->isClicked()) {
                $pdf = $this->cvPdfGeneratorService->generatePdfBinary($data);

                return new Response($pdf, Response::HTTP_OK, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="cv_'.preg_replace('/[^a-z0-9]+/i', '_', strtolower($data->fullName)).'.pdf"',
                ]);
            }

            if ($form->get('save')->isClicked()) {
                $relPath = $this->cvPdfGeneratorService->savePdfForUser($data, $user, $this->cvUploadDir);
                $request->getSession()->set('candidate_generated_cv_relpath', $relPath);
                $this->addFlash('success', 'CV généré et enregistré. Vous pouvez maintenant l’utiliser lors d’une candidature.');

                return $this->redirectToRoute('app_candidate_cv_builder');
            }
        }

        return $this->render('recrutement/cv/builder.html.twig', [
            'form' => $form,
            'saved_cv_relpath' => $request->getSession()->get('candidate_generated_cv_relpath'),
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function buildCvDataFromPayload(array $payload, mixed $photo): CvBuilderData
    {
        $education = [];
        foreach (($payload['education'] ?? []) as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $degree = trim((string) ($row['degree'] ?? ''));
            $institution = trim((string) ($row['institution'] ?? ''));
            $year = trim((string) ($row['year'] ?? ''));
            if ($degree === '' && $institution === '' && $year === '') {
                continue;
            }
            $education[] = ['degree' => $degree, 'institution' => $institution, 'year' => $year];
        }

        $experience = [];
        foreach (($payload['experience'] ?? []) as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $jobTitle = trim((string) ($row['jobTitle'] ?? ''));
            $company = trim((string) ($row['company'] ?? ''));
            $duration = trim((string) ($row['duration'] ?? ''));
            $description = trim((string) ($row['description'] ?? ''));
            if ($jobTitle === '' && $company === '' && $duration === '' && $description === '') {
                continue;
            }
            $experience[] = [
                'jobTitle' => $jobTitle,
                'company' => $company,
                'duration' => $duration,
                'description' => $description,
            ];
        }

        return new CvBuilderData(
            trim((string) ($payload['fullName'] ?? '')),
            trim((string) ($payload['email'] ?? '')),
            $this->nullIfBlank($payload['phone'] ?? null),
            $this->nullIfBlank($payload['address'] ?? null),
            trim((string) ($payload['professionalSummary'] ?? '')),
            $education,
            $experience,
            trim((string) ($payload['skills'] ?? '')),
            $this->nullIfBlank($payload['languages'] ?? null),
            \in_array(($payload['template'] ?? 'modern'), ['classic', 'modern'], true) ? (string) $payload['template'] : 'modern',
            $this->photoToDataUri($photo),
        );
    }

    private function nullIfBlank(mixed $v): ?string
    {
        if (!\is_string($v)) {
            return null;
        }

        $t = trim($v);

        return $t === '' ? null : $t;
    }

    private function photoToDataUri(mixed $photo): ?string
    {
        if (!$photo instanceof UploadedFile || !$photo->isValid()) {
            return null;
        }

        $raw = @file_get_contents($photo->getPathname());
        if ($raw === false || $raw === '') {
            return null;
        }

        $mime = $photo->getMimeType() ?: 'application/octet-stream';

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }
}
