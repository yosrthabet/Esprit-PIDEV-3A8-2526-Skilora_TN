<?php

declare(strict_types=1);

namespace App\Formation\Controller;

use App\Controller\AppController;
use App\Entity\User;
use App\Formation\Entity\Enrollment;
use App\Formation\Entity\Certificate;
use App\Formation\Entity\Formation;
use App\Formation\Entity\FormationMaterial;
use App\Formation\Entity\FormationModule;
use App\Formation\Entity\FormationReview;
use App\Formation\Entity\Quiz;
use App\Formation\Entity\QuizResult;
use App\Formation\Entity\ReviewVote;
use App\Formation\Repository\QuizRepository;
use App\Formation\Repository\QuizResultRepository;
use App\Formation\Repository\ReviewVoteRepository;
use App\Formation\EnrollmentStatus;
use App\Formation\FormationLevel;
use App\Formation\FormationStatus;
use App\Formation\Repository\CertificateRepository;
use App\Formation\Repository\EnrollmentRepository;
use App\Formation\Repository\FormationReviewRepository;
use App\Formation\Repository\FormationRepository;
use App\Formation\Service\EnrollmentService;
use App\Formation\Service\FormationAiReviewService;
use App\Formation\Service\FormationNotifier;
use App\Formation\Service\FormationCertificateSignatureHandler;
use App\Formation\Service\FormationProgressService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class FormationController extends AppController
{
    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly CertificateRepository $certificateRepository,
        private readonly FormationReviewRepository $reviewRepository,
        private readonly ReviewVoteRepository $reviewVoteRepository,
        private readonly QuizRepository $quizRepository,
        private readonly QuizResultRepository $quizResultRepository,
        private readonly EnrollmentService $enrollmentService,
        private readonly FormationNotifier $notifier,
        private readonly FormationProgressService $progressService,
        private readonly FormationCertificateSignatureHandler $signatureHandler,
        private readonly FormationAiReviewService $aiReviewService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/formations', name: 'app_formations', methods: ['GET'])]
    #[Route('/formations', name: 'app_formation_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $category = trim($request->query->getString('category')) ?: null;
        $query = trim($request->query->getString('q')) ?: null;
        $level = FormationLevel::tryFrom($request->query->getString('level'));

        return $this->render('formation/catalog/index.html.twig', [
            'formations' => $this->formationRepository->findPublished($category, 40, $query, $level),
            'category' => $category,
            'q' => $query,
            'level' => $level?->value,
            'levels' => FormationLevel::cases(),
        ]);
    }

    #[Route('/formations/{id}', name: 'app_formation_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function show(Formation $formation): Response
    {
        $user = $this->getUser() instanceof User ? $this->getUser() : null;
        if (!$formation->isPublished() && ($user === null || (!$this->canManageFormation($user, $formation) && !$user->isAdmin()))) {
            throw $this->createAccessDeniedException();
        }
        $enrollment = $user !== null ? $this->enrollmentRepository->findOneForUserAndFormation($user, $formation) : null;

        return $this->render('formation/catalog/show.html.twig', [
            'formation' => $formation,
            'enrollment' => $enrollment,
            'review' => $user !== null ? $this->reviewRepository->findOneForUserAndFormation($user, $formation) : null,
            'review_summary' => $this->reviewRepository->summarizeForFormation($formation),
            'can_review' => $enrollment !== null && $enrollment->getStatus() === EnrollmentStatus::COMPLETED,
            'can_edit_modules' => $user !== null && $this->canManageFormation($user, $formation),
        ]);
    }

    #[Route('/formations/{id}/enroll', name: 'app_formation_enroll', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_USER')]
    public function enroll(Request $request, Formation $formation): Response
    {
        $user = $this->getAppUser();
        if ($user->isAdmin()) {
            throw $this->createAccessDeniedException('Administrators cannot enroll in formations.');
        }
        if (!$this->isCsrfTokenValid('enroll_formation_' . $formation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $existing = $this->enrollmentRepository->findOneForUserAndFormation($user, $formation);

        try {
            $enrollment = $this->enrollmentService->enroll($user, $formation);
        } catch (\RuntimeException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_formations');
        }

        if ($existing === null) {
            $this->notifier->notifyTrainerEnrollment($enrollment);
        }

        $price = (float) ($formation->getPriceAmount() ?? '0');
        $msg = $price > 0
            ? sprintf('Enrolled! %.2f TND deducted from your wallet.', $price)
            : 'Formation added to your learning space.';
        $this->addFlash('success', $msg);

        return $this->redirectToRoute('app_learning_show', ['id' => $enrollment->getId()]);
    }

    #[Route('/formations/{id}/review', name: 'app_formation_review', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function review(Request $request, Formation $formation): Response
    {
        $user = $this->getAppUser();
        $enrollment = $this->enrollmentRepository->findOneForUserAndFormation($user, $formation);
        if ($enrollment === null || $enrollment->getStatus() !== EnrollmentStatus::COMPLETED) {
            throw $this->createAccessDeniedException('Only completed learners can review this formation.');
        }
        if (!$this->isCsrfTokenValid('formation_review_' . $formation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $review = $this->reviewRepository->findOneForUserAndFormation($user, $formation) ?? (new FormationReview())
            ->setUser($user)
            ->setFormation($formation);
        $review
            ->setRating($request->request->getInt('rating', 5))
            ->setComment(trim($request->request->getString('comment')) ?: null);

        $this->entityManager->persist($review);
        $this->entityManager->flush();
        $this->addFlash('success', 'Thank you for your review!');

        return $this->redirectToRoute('app_learning_show', ['id' => $enrollment->getId()]);
    }

    #[Route('/formations/reviews/{id}/vote', name: 'app_formation_review_vote', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function reviewVote(Request $request, FormationReview $review): Response
    {
        $user = $this->getAppUser();
        if (!$this->isCsrfTokenValid('review_vote_' . $review->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $helpful = $request->request->getString('vote') === 'helpful';
        $existing = $this->reviewVoteRepository->findOneForUserAndReview($user, $review);
        if ($existing !== null) {
            $existing->setHelpful($helpful);
        } else {
            $vote = (new ReviewVote())->setReview($review)->setUser($user)->setHelpful($helpful);
            $this->entityManager->persist($vote);
        }
        $this->entityManager->flush();

        return $this->redirectToRoute('app_formation_show', ['id' => $review->getFormation()->getId()]);
    }

    #[Route('/learning', name: 'app_learning', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function learning(): Response
    {
        return $this->render('formation/learning/index.html.twig', [
            'enrollments' => $this->enrollmentRepository->findForUser($this->getAppUser()),
            'progress_service' => $this->progressService,
        ]);
    }

    #[Route('/learning/{id}', name: 'app_learning_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function learningShow(Enrollment $enrollment): Response
    {
        $this->assertOwnEnrollment($enrollment);
        $completedModuleIds = [];
        foreach ($enrollment->getLessonProgress() as $progress) {
            if ($progress->isComplete() && $progress->getModule()->getId() !== null) {
                $completedModuleIds[] = $progress->getModule()->getId();
            }
        }

        $user = $this->getAppUser();
        $hasReviewed = $this->reviewRepository->findOneForUserAndFormation($user, $enrollment->getFormation()) !== null;

        return $this->render('formation/learning/show.html.twig', [
            'enrollment' => $enrollment,
            'progress_percent' => $this->progressService->getCompletionPercent($enrollment),
            'completed_module_ids' => $completedModuleIds,
            'has_reviewed' => $hasReviewed,
        ]);
    }

    #[Route('/learning/{id}/progress', name: 'app_learning_progress', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function progress(Request $request, Enrollment $enrollment): Response
    {
        $this->assertOwnEnrollment($enrollment);
        if (!$this->isCsrfTokenValid('learning_progress_' . $enrollment->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $moduleId = $request->request->getInt('module_id');
        foreach ($enrollment->getFormation()->getModules() as $module) {
            if ($module->getId() === $moduleId) {
                $this->progressService->recordProgress($enrollment, $module, 100);
                if ($this->progressService->getCompletionPercent($enrollment) === 100) {
                    $certificate = $this->progressService->issueCertificate($enrollment);
                    $this->notifier->notifyCertificateIssued($certificate);
                    $this->addFlash('success', 'Formation completed. Your certificate is ready.');
                }

                return $this->redirectToRoute('app_learning_show', ['id' => $enrollment->getId()]);
            }
        }

        throw $this->createNotFoundException('Module not found for this enrollment.');
    }

    #[Route('/certificates', name: 'app_certificates', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function certificates(): Response
    {
        $certificates = [];
        foreach ($this->enrollmentRepository->findForUser($this->getAppUser()) as $enrollment) {
            if ($enrollment->getCertificate() !== null) {
                $certificates[] = $enrollment->getCertificate();
            }
        }

        return $this->render('formation/certificates/index.html.twig', [
            'certificates' => $certificates,
        ]);
    }

    #[Route('/certificates/{id}', name: 'app_certificate_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function certificateShow(Certificate $certificate): Response
    {
        $this->assertOwnEnrollment($certificate->getEnrollment());

        return $this->render('formation/certificates/show.html.twig', [
            'certificate' => $certificate,
            'signature_data_uri' => $this->signatureHandler->getSignatureDataUri($certificate->getEnrollment()->getFormation()),
        ]);
    }

    #[Route('/certificate/verify/{verificationId}', name: 'app_certificate_verify', methods: ['GET'])]
    #[Route('/certificate/verify/{verificationId}', name: 'certificate_verify', methods: ['GET'])]
    public function certificateVerify(string $verificationId): Response
    {
        $cert = $this->certificateRepository->findOneByVerificationId($verificationId);
        return $this->render('formation/certificates/verify.html.twig', [
            'certificate' => $cert,
            'verification_id' => $verificationId,
            'signature_data_uri' => $cert ? $this->signatureHandler->getSignatureDataUri($cert->getEnrollment()->getFormation()) : null,
        ]);
    }

    #[Route('/certificates/{id}/preview', name: 'app_certificate_preview', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function certificatePreview(Certificate $certificate): Response
    {
        $this->assertOwnEnrollment($certificate->getEnrollment());

        return $this->render('formation/certificates/preview.html.twig', [
            'certificate' => $certificate,
            'verification_url' => $this->certificateVerificationUrl($certificate),
            'signature_data_uri' => $this->signatureHandler->getSignatureDataUri($certificate->getEnrollment()->getFormation()),
        ]);
    }

    #[Route('/certificates/{id}/qr', name: 'app_certificate_qr', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[Route('/certificate/{id}/qr', name: 'certificate_qr', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function certificateQr(Certificate $certificate): Response
    {
        $this->assertOwnEnrollment($certificate->getEnrollment());
        $url = $this->certificateVerificationUrl($certificate);

        return $this->render('formation/certificates/qr.html.twig', [
            'certificate' => $certificate,
            'verification_url' => $url,
            'qr_data_uri' => $this->qrDataUri($url),
        ]);
    }

    #[Route('/certificates/{id}/download', name: 'app_certificate_download', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[Route('/certificates/{id}/pdf', name: 'certificate_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function certificateDownload(Certificate $certificate): Response
    {
        $this->assertOwnEnrollment($certificate->getEnrollment());
        $html = $this->renderView('formation/certificates/pdf.html.twig', [
            'certificate' => $certificate,
            'verification_url' => $this->certificateVerificationUrl($certificate),
            'signature_data_uri' => $this->signatureHandler->getSignatureDataUri($certificate->getEnrollment()->getFormation()),
        ]);

        if (class_exists(\Dompdf\Dompdf::class)) {
            $dompdf = new \Dompdf\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $response = new Response($dompdf->output());
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, 'skilora-certificate-' . $certificate->getVerificationId() . '.pdf'));

            return $response;
        }

        return new Response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    #[Route('/trainer/formations', name: 'app_trainer_formations', methods: ['GET'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerIndex(Request $request): Response
    {
        $query = trim($request->query->getString('q')) ?: null;
        $page = max(1, $request->query->getInt('page', 1));
        $perPage = 10;
        $total = $this->formationRepository->countForTrainer($this->getAppUser(), $query);

        return $this->render('formation/trainer/index.html.twig', [
            'formations' => $this->formationRepository->findForTrainerPaginated($this->getAppUser(), $query, $perPage, ($page - 1) * $perPage),
            'q' => $query,
            'page' => $page,
            'page_count' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ]);
    }

    #[Route('/trainer/formations/new', name: 'app_trainer_formation_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerNew(Request $request): Response
    {
        $formation = (new Formation())->setTrainer($this->getAppUser());
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('trainer_formation_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            if (($errors = $this->formationRequestErrors($request)) !== []) {
                return $this->renderFormationFormWithErrors('formation/trainer/form.html.twig', $formation, 'Create formation', $errors);
            }
            $this->applyFormationRequest($formation, $request);
            $this->entityManager->persist($formation);
            $this->entityManager->flush();
            $this->signatureHandler->handleSignatureFromRequest($request, $formation);
            $this->notifier->notifyAdminsNewFormation($formation);

            return $this->redirectToRoute('app_trainer_formation_modules', ['id' => $formation->getId()]);
        }

        return $this->render('formation/trainer/form.html.twig', [
            'formation' => $formation,
            'levels' => FormationLevel::cases(),
            'action' => 'Create formation',
        ]);
    }

    #[Route('/trainer/formations/{id}', name: 'app_trainer_formation_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerShow(Formation $formation): Response
    {
        $this->assertTrainerOwns($formation);

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()]);
    }

    #[Route('/trainer/formations/{id}/edit', name: 'app_trainer_formation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerEdit(Request $request, Formation $formation): Response
    {
        $this->assertTrainerOwns($formation);
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('trainer_formation_edit_' . $formation->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            if (($errors = $this->formationRequestErrors($request)) !== []) {
                return $this->renderFormationFormWithErrors('formation/trainer/form.html.twig', $formation, 'Update formation', $errors);
            }
            $this->applyFormationRequest($formation, $request);
            $this->entityManager->flush();
            $this->signatureHandler->handleSignatureFromRequest($request, $formation);

            return $this->redirectToRoute('app_trainer_formations');
        }

        return $this->render('formation/trainer/form.html.twig', [
            'formation' => $formation,
            'levels' => FormationLevel::cases(),
            'action' => 'Update formation',
        ]);
    }

    #[Route('/trainer/formations/{id}/signature-preview', name: 'app_trainer_formation_signature_preview', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerSignaturePreview(Formation $formation): Response
    {
        $this->assertTrainerOwns($formation);
        $dataUri = $this->signatureHandler->getSignatureDataUri($formation);
        if (null === $dataUri) {
            throw $this->createNotFoundException('No signature found.');
        }
        $binary = base64_decode(explode(',', $dataUri, 2)[1] ?? '', true);
        $response = new Response($binary ?: '');
        $response->headers->set('Content-Type', 'image/png');
        $response->headers->set('Content-Disposition', 'inline; filename="signature.png"');
        return $response;
    }

    #[Route('/trainer/formations/{id}/submit-review', name: 'app_trainer_formation_submit_review', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerSubmitForReview(Request $request, Formation $formation): Response
    {
        $this->assertTrainerOwns($formation);
        if (!$this->isCsrfTokenValid('trainer_formation_submit_' . $formation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        if (!in_array($formation->getStatus(), [FormationStatus::DRAFT, FormationStatus::REFUSED], true)) {
            $this->addFlash('error', 'This formation cannot be submitted for review in its current state.');
            return $this->redirectToRoute('app_trainer_formation_modules', ['id' => $formation->getId()]);
        }
        if ($formation->getModules()->isEmpty()) {
            $this->addFlash('error', 'Add at least one module before submitting for review.');
            return $this->redirectToRoute('app_trainer_formation_modules', ['id' => $formation->getId()]);
        }
        if (null === $formation->getCertificateSignatureFilename()) {
            $this->addFlash('error', 'Please add your certificate signature before submitting.');
            return $this->redirectToRoute('app_trainer_formation_modules', ['id' => $formation->getId()]);
        }
        $formation->setStatus(FormationStatus::PENDING_REVIEW);
        $this->entityManager->flush();

        // Trigger async AI review
        try {
            $this->aiReviewService->reviewFormation($formation);
        } catch (\Throwable) {
            // If AI review fails, stay in pending_review for manual review
        }

        if ($formation->getStatus() === FormationStatus::PUBLISHED) {
            $this->addFlash('success', 'Your formation passed AI review and is now published!');
        } elseif ($formation->getStatus() === FormationStatus::REFUSED) {
            $this->addFlash('error', 'AI review did not approve your formation: ' . ($formation->getReviewNote() ?? 'quality below threshold'));
            return $this->redirectToRoute('app_trainer_formation_modules', ['id' => $formation->getId()]);
        } else {
            $this->addFlash('success', 'Formation submitted for review. You will be notified once reviewed.');
        }

        return $this->redirectToRoute('app_trainer_formations');
    }

    #[Route('/trainer/formations/{id}/delete', name: 'app_trainer_formation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerDelete(Request $request, Formation $formation): Response
    {
        $this->assertTrainerOwns($formation);
        if (!$this->isCsrfTokenValid('trainer_formation_delete_' . $formation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $this->entityManager->remove($formation);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_trainer_formations');
    }

    #[Route('/trainer/formations/{id}/modules', name: 'app_trainer_formation_modules', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerModules(Request $request, Formation $formation): Response
    {
        $this->assertTrainerOwns($formation);
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('trainer_module_' . $formation->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            $module = (new FormationModule())
                ->setTitle(trim($request->request->getString('title')))
                ->setDescription(trim($request->request->getString('description')) ?: null)
                ->setPosition($formation->getModules()->count() + 1)
                ->setDurationMinutes(max(0, $request->request->getInt('duration_minutes')));
            $formation->addModule($module);
            $this->entityManager->persist($module);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_trainer_formation_modules', ['id' => $formation->getId()]);
        }

        return $this->render('formation/trainer/modules.html.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/trainer/formations/{id}/materials', name: 'app_trainer_formation_materials', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerMaterials(Formation $formation): Response
    {
        $this->assertTrainerOwns($formation);

        return $this->render('formation/trainer/materials.html.twig', [
            'formation' => $formation,
        ]);
    }

    #[Route('/trainer/formations/{id}/modules/{moduleId}/delete', name: 'app_trainer_formation_module_delete', methods: ['POST'], requirements: ['id' => '\d+', 'moduleId' => '\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerModuleDelete(Request $request, Formation $formation, int $moduleId): Response
    {
        $this->assertTrainerOwns($formation);
        if (!$this->isCsrfTokenValid('trainer_module_delete_' . $formation->getId() . '_' . $moduleId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        foreach ($formation->getModules() as $module) {
            if ($module->getId() === $moduleId) {
                $this->entityManager->remove($module);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_trainer_formation_modules', ['id' => $formation->getId()]);
            }
        }

        throw $this->createNotFoundException('Module not found for this formation.');
    }

    #[Route('/trainer/formations/{id}/modules/{moduleId}/materials', name: 'app_trainer_formation_material_add', methods: ['POST'], requirements: ['id' => '\\d+', 'moduleId' => '\\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerMaterialAdd(Request $request, Formation $formation, int $moduleId): Response
    {
        $this->assertTrainerOwns($formation);
        if (!$this->isCsrfTokenValid('trainer_material_' . $formation->getId() . '_' . $moduleId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        foreach ($formation->getModules() as $module) {
            if ($module->getId() === $moduleId) {
                $material = (new FormationMaterial())
                    ->setTitle(trim($request->request->getString('title')))
                    ->setResourceUrl(trim($request->request->getString('resource_url')))
                    ->setKind(trim($request->request->getString('kind')) ?: 'link')
                    ->setPosition($module->getMaterials()->count() + 1);
                $module->addMaterial($material);
                $this->entityManager->persist($material);
                $this->entityManager->flush();

                return $this->redirectToRoute('app_trainer_formation_modules', ['id' => $formation->getId()]);
            }
        }

        throw $this->createNotFoundException('Module not found for this formation.');
    }

    #[Route('/trainer/formations/{id}/modules/{moduleId}/materials/{materialId}/delete', name: 'app_trainer_formation_material_delete', methods: ['POST'], requirements: ['id' => '\d+', 'moduleId' => '\d+', 'materialId' => '\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerMaterialDelete(Request $request, Formation $formation, int $moduleId, int $materialId): Response
    {
        $this->assertTrainerOwns($formation);
        if (!$this->isCsrfTokenValid('trainer_material_delete_' . $formation->getId() . '_' . $materialId, $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        foreach ($formation->getModules() as $module) {
            if ($module->getId() !== $moduleId) {
                continue;
            }
            foreach ($module->getMaterials() as $material) {
                if ($material->getId() === $materialId) {
                    $this->entityManager->remove($material);
                    $this->entityManager->flush();

                    return $this->redirectToRoute('app_trainer_formation_materials', ['id' => $formation->getId()]);
                }
            }
        }

        throw $this->createNotFoundException('Material not found for this formation.');
    }

    #[Route('/trainer/formations/{id}/students', name: 'app_trainer_formation_students', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_TRAINER')]
    public function trainerStudents(Formation $formation): Response
    {
        $this->assertTrainerOwns($formation);

        return $this->render('formation/trainer/students.html.twig', [
            'formation' => $formation,
            'enrollments' => $this->enrollmentRepository->findForFormation($formation),
            'progress_service' => $this->progressService,
        ]);
    }

    #[Route('/admin/formations', name: 'app_admin_formations', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminIndex(): Response
    {
        return $this->render('formation/admin/index.html.twig', [
            'formations' => $this->formationRepository->findBy([], ['updatedAt' => 'DESC'], 80),
        ]);
    }

    #[Route('/admin/formations/new', name: 'app_admin_formation_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminNew(Request $request): Response
    {
        $formation = (new Formation())->setTrainer($this->getAppUser());
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_formation_new', $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            if (($errors = $this->formationRequestErrors($request)) !== []) {
                return $this->renderFormationFormWithErrors('formation/admin/form.html.twig', $formation, 'Create formation', $errors);
            }
            $this->applyFormationRequest($formation, $request);
            $this->entityManager->persist($formation);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('formation/admin/form.html.twig', [
            'formation' => $formation,
            'levels' => FormationLevel::cases(),
            'action' => 'Create formation',
        ]);
    }

    #[Route('/admin/formations/{id}', name: 'app_admin_formation_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminShow(Formation $formation): Response
    {
        return $this->render('formation/admin/show.html.twig', ['formation' => $formation]);
    }

    #[Route('/admin/formations/{id}/edit', name: 'app_admin_formation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminEdit(Request $request, Formation $formation): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('admin_formation_edit_' . $formation->getId(), $request->request->getString('_token'))) {
                throw $this->createAccessDeniedException('Invalid CSRF token.');
            }
            if (($errors = $this->formationRequestErrors($request)) !== []) {
                return $this->renderFormationFormWithErrors('formation/admin/form.html.twig', $formation, 'Update formation', $errors);
            }
            $this->applyFormationRequest($formation, $request);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_admin_formation_show', ['id' => $formation->getId()]);
        }

        return $this->render('formation/admin/form.html.twig', [
            'formation' => $formation,
            'levels' => FormationLevel::cases(),
            'action' => 'Update formation',
        ]);
    }

    #[Route('/admin/formations/{id}/publish', name: 'app_admin_formation_publish', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminPublish(Request $request, Formation $formation): Response
    {
        $this->assertAdminToken($request, $formation, 'publish');
        $formation->publish();
        $this->entityManager->flush();
        $this->notifier->notifyTrainerPublished($formation);

        return $this->redirectToRoute('app_admin_formation_show', ['id' => $formation->getId()]);
    }

    #[Route('/admin/formations/{id}/archive', name: 'app_admin_formation_archive', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminArchive(Request $request, Formation $formation): Response
    {
        $this->assertAdminToken($request, $formation, 'archive');
        $formation->archive();
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_formation_show', ['id' => $formation->getId()]);
    }

    #[Route('/admin/formations/{id}/delete', name: 'app_admin_formation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminDelete(Request $request, Formation $formation): Response
    {
        $this->assertAdminToken($request, $formation, 'delete');
        $this->entityManager->remove($formation);
        $this->entityManager->flush();

        return $this->redirectToRoute('app_admin_formations');
    }

    #[Route('/admin/certificates', name: 'app_admin_certificates', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminCertificates(): Response
    {
        return $this->render('formation/admin/certificates.html.twig', [
            'certificates' => $this->certificateRepository->findAllRecent(50),
        ]);
    }

    private function applyFormationRequest(Formation $formation, Request $request): void
    {
        $formation
            ->setTitle(trim($request->request->getString('title')))
            ->setDescription(trim($request->request->getString('description')) ?: null)
            ->setCategory(trim($request->request->getString('category')) ?: 'General')
            ->setLevel(FormationLevel::tryFrom($request->request->getString('level')) ?? FormationLevel::BEGINNER)
            ->setDurationHours(max(0, $request->request->getInt('duration_hours')))
            ->setPriceAmount($request->request->getString('price_amount') !== '' ? $request->request->getString('price_amount') : null);
    }

    /** @return list<string> */
    private function formationRequestErrors(Request $request): array
    {
        $errors = [];
        $title = trim($request->request->getString('title'));
        $description = trim($request->request->getString('description'));
        $category = trim($request->request->getString('category'));
        $price = trim($request->request->getString('price_amount'));
        $duration = $request->request->getInt('duration_hours');

        if (mb_strlen($title) < 3 || mb_strlen($title) > 180) {
            $errors[] = 'Title must be between 3 and 180 characters.';
        }
        if ($description !== '' && mb_strlen($description) > 5000) {
            $errors[] = 'Description must stay under 5000 characters.';
        }
        if ($category !== '' && mb_strlen($category) > 120) {
            $errors[] = 'Category must stay under 120 characters.';
        }
        if ($duration < 1) {
            $errors[] = 'Duration must be at least 1 hour.';
        }
        if ($price !== '' && (!is_numeric($price) || (float) $price < 0)) {
            $errors[] = 'Price must be a positive number or blank.';
        }

        return $errors;
    }

    /** @param list<string> $errors */
    private function renderFormationFormWithErrors(string $template, Formation $formation, string $action, array $errors): Response
    {
        return $this->render($template, [
            'formation' => $formation,
            'levels' => FormationLevel::cases(),
            'action' => $action,
            'errors' => $errors,
        ], new Response(status: 422));
    }

    private function assertOwnEnrollment(Enrollment $enrollment): void
    {
        if ($enrollment->getUser()->getId() !== $this->getAppUser()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function assertTrainerOwns(Formation $formation): void
    {
        if (!$this->canManageFormation($this->getAppUser(), $formation)) {
            throw $this->createAccessDeniedException();
        }
    }

    private function canManageFormation(User $user, Formation $formation): bool
    {
        return $formation->getTrainer()->getId() === $user->getId();
    }

    private function assertAdminToken(Request $request, Formation $formation, string $action): void
    {
        if (!$this->isCsrfTokenValid('admin_formation_' . $action . '_' . $formation->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }

    #[Route('/formations/{id}/quizzes', name: 'app_formation_quizzes', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function quizzes(Formation $formation): Response
    {
        $user = $this->getAppUser();
        $enrollment = $this->enrollmentRepository->findOneForUserAndFormation($user, $formation);
        if ($enrollment === null) {
            throw $this->createAccessDeniedException('You must be enrolled.');
        }

        return $this->render('formation/quiz/index.html.twig', [
            'formation' => $formation,
            'quizzes' => $this->quizRepository->findPublishedForFormation($formation),
            'results' => $this->quizResultRepository->findForStudent($user),
        ]);
    }

    #[Route('/quizzes/{id}/take', name: 'app_quiz_take', methods: ['GET'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function takeQuiz(Quiz $quiz): Response
    {
        $user = $this->getAppUser();
        $enrollment = $this->enrollmentRepository->findOneForUserAndFormation($user, $quiz->getFormation());
        if ($enrollment === null) {
            throw $this->createAccessDeniedException('You must be enrolled.');
        }

        return $this->render('formation/quiz/take.html.twig', [
            'quiz' => $quiz,
            'questions' => $quiz->getQuestions(),
        ]);
    }

    #[Route('/quizzes/{id}/submit', name: 'app_quiz_submit', methods: ['POST'], requirements: ['id' => '\\d+'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function submitQuiz(Request $request, Quiz $quiz): Response
    {
        $user = $this->getAppUser();
        $enrollment = $this->enrollmentRepository->findOneForUserAndFormation($user, $quiz->getFormation());
        if ($enrollment === null) {
            throw $this->createAccessDeniedException('You must be enrolled.');
        }
        if (!$this->isCsrfTokenValid('quiz_submit_' . $quiz->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $answers = [];
        $earnedPoints = 0;
        $totalPoints = 0;
        foreach ($quiz->getQuestions() as $question) {
            $qid = $question->getId();
            if ($qid === null) {
                continue;
            }
            $selected = $request->request->getInt('q_' . $qid, -1);
            $answers[$qid] = $selected;
            $totalPoints += $question->getPoints();
            if ($question->isCorrect($selected)) {
                $earnedPoints += $question->getPoints();
            }
        }

        $result = (new QuizResult())
            ->setQuiz($quiz)
            ->setStudent($user)
            ->setAnswers($answers);
        $result->complete($earnedPoints, $totalPoints, $quiz->getPassingScore());
        $this->entityManager->persist($result);
        $this->entityManager->flush();

        return $this->render('formation/quiz/result.html.twig', [
            'quiz' => $quiz,
            'result' => $result,
        ]);
    }

    private function certificateVerificationUrl(Certificate $certificate): string
    {
        return $this->generateUrl('app_certificate_verify', ['verificationId' => $certificate->getVerificationId()], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function qrDataUri(string $data): ?string
    {
        if (!class_exists(\Endroid\QrCode\Builder\Builder::class)) {
            return null;
        }

        try {
            $result = \Endroid\QrCode\Builder\Builder::create()
                ->writer(new \Endroid\QrCode\Writer\PngWriter())
                ->data($data)
                ->encoding(new \Endroid\QrCode\Encoding\Encoding('UTF-8'))
                ->size(320)
                ->margin(12)
                ->build();

            return $result->getDataUri();
        } catch (\Throwable) {
            return null;
        }
    }
}
