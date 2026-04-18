<?php

declare(strict_types=1);

namespace App\Controller\Formation;

use App\Certificate\Branding\CertificateBrandingAssetResolverInterface;
use App\Entity\Certificate;
use App\Entity\User;
use App\Repository\CertificateRepository;
use App\Repository\EnrollmentRepository;
use App\Service\CertificatePdfGenerator;
use App\Service\CertificateQrCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class LearningController extends AbstractController
{
    public function __construct(
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly CertificateRepository $certificateRepository,
        private readonly CertificatePdfGenerator $certificatePdfGenerator,
        private readonly CertificateQrCodeService $certificateQrCodeService,
        private readonly CertificateBrandingAssetResolverInterface $certificateBrandingAssetResolver,
    ) {
    }

    #[Route('/my-formations', name: 'app_my_formations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myFormations(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $enrollments = $this->enrollmentRepository->findByUserOrdered($user);

        return $this->render('learning/my_formations.html.twig', [
            'enrollments' => $enrollments,
        ]);
    }

    #[Route('/my-certificates', name: 'app_my_certificates', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myCertificates(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $certificates = $this->certificateRepository->findByUserOrdered($user);

        return $this->render('learning/my_certificates.html.twig', [
            'certificates' => $certificates,
        ]);
    }

    #[Route('/certificates/{id}/pdf', name: 'app_certificate_pdf', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function downloadCertificate(int $id): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $certificate = $this->certificateRepository->findOneByUserAndCertificateId($user, $id);
        if (!$certificate instanceof Certificate) {
            throw $this->createNotFoundException('Certificate not found.');
        }

        $pdf = $this->certificatePdfGenerator->generate($certificate);
        $filename = sprintf('certificate-%d.pdf', $certificate->getId());

        return new Response($pdf, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    #[Route('/certificate/{id}/preview', name: 'app_certificate_preview', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[Route('/certificates/{id}/preview', name: 'app_certificate_preview_legacy', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function previewCertificate(int $id): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $certificate = $this->certificateRepository->findOneByUserAndCertificateId($user, $id);
        if (!$certificate instanceof Certificate) {
            throw $this->createNotFoundException('Certificate not found.');
        }

        return $this->render('certificate/preview.html.twig', [
            'certificate' => $certificate,
            'verificationUrl' => $this->certificateQrCodeService->getVerificationUrl($certificate),
            'qrCodeDataUri' => $this->certificateQrCodeService->generateDataUri($certificate, 80),
            'formationDirectorSignatureDataUri' => $this->certificateBrandingAssetResolver->getDirectorSignatureDataUri($certificate),
        ]);
    }

    #[Route('/certificate/{id}/qr', name: 'app_certificate_qr', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function qrCertificate(int $id): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $certificate = $this->certificateRepository->findOneByUserAndCertificateId($user, $id);
        if (!$certificate instanceof Certificate) {
            throw $this->createNotFoundException('Certificate not found.');
        }

        return $this->render('certificate/qr.html.twig', [
            'certificate' => $certificate,
            'verificationUrl' => $this->certificateQrCodeService->getVerificationUrl($certificate),
            'qrCodeDataUri' => $this->certificateQrCodeService->generateDataUri($certificate, 320),
        ]);
    }
}
