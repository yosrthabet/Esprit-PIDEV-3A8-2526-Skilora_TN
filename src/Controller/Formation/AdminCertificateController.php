<?php

declare(strict_types=1);

namespace App\Controller\Formation;

use App\Repository\CertificateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/certificates')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCertificateController extends AbstractController
{
    #[Route(name: 'app_admin_certificates_index', methods: ['GET'])]
    public function index(CertificateRepository $certificateRepository): Response
    {
        return $this->render('admin/certificates/index.html.twig', [
            'certificates' => $certificateRepository->findBy([], ['issuedAt' => 'DESC']),
        ]);
    }
}
