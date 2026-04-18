<?php

declare(strict_types=1);

namespace App\Controller\Formation;

use App\Repository\CertificateRepository;
use App\Service\CertificateQrCodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CertificateVerificationController extends AbstractController
{
    #[Route('/test-url', name: 'test_url', methods: ['GET'])]
    public function testUrl(): Response
    {
        return new Response('Server is reachable');
    }

    #[Route('/certificate/verify/{verificationId}', name: 'certificate_verify', methods: ['GET'])]
    public function verify(string $verificationId, CertificateRepository $certificateRepository): Response
    {
        $certificate = $certificateRepository->findOneByVerificationId($verificationId);

        if (null === $certificate) {
            return $this->render('certificate/verify_invalid.html.twig', [
                'verificationId' => $verificationId,
            ], new Response('', Response::HTTP_NOT_FOUND));
        }

        return $this->render('certificate/verify.html.twig', [
            'certificate' => $certificate,
        ]);
    }
}
