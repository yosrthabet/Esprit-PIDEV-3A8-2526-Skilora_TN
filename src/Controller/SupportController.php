<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SupportController extends AbstractController
{
    #[Route('/support-space', name: 'app_support')]
    public function index(): Response
    {
        // No security checks: always send client flow here.
        return $this->redirectToRoute('support_index');
    }
}
