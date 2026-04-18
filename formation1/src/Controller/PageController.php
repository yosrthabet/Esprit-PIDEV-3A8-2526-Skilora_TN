<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Redirects sidebar nav placeholders to actual module pages.
 */
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class PageController extends AbstractController
{
    #[Route('/recruitment', name: 'app_recruitment')]
    public function recruitment(): Response
    {
        return $this->redirectToRoute('app_candidate_offres_index');
    }

    #[Route('/finance', name: 'app_finance')]
    public function finance(): Response
    {
        return $this->redirectToRoute('admin_finance_index');
    }

    #[Route('/support-space', name: 'app_support')]
    public function support(): Response
    {
        return $this->redirectToRoute('support_index');
    }

    #[Route('/community', name: 'app_community')]
    public function community(): Response
    {
        return $this->redirectToRoute('app_community_posts');
    }

    #[Route('/jobs', name: 'app_jobs')]
    public function jobs(): Response
    {
        return $this->redirectToRoute('app_candidate_offres_index');
    }

    #[Route('/applications', name: 'app_applications')]
    public function applications(): Response
    {
        return $this->redirectToRoute('app_candidate_area_applications');
    }

    #[Route('/post-job', name: 'app_post_job')]
    public function postJob(): Response
    {
        return $this->redirectToRoute('app_employer_job_offer_new');
    }

    #[Route('/active-offers', name: 'app_active_offers')]
    public function activeOffers(): Response
    {
        return $this->redirectToRoute('app_employer_job_offer_index');
    }

    #[Route('/inbox', name: 'app_inbox')]
    public function inbox(): Response
    {
        return $this->redirectToRoute('app_employer_applications');
    }

    #[Route('/interviews', name: 'app_interviews')]
    public function interviews(): Response
    {
        return $this->redirectToRoute('app_employer_interviews');
    }

    #[Route('/mentorship', name: 'app_mentorship')]
    public function mentorship(): Response
    {
        return $this->render('pages/placeholder.html.twig', [
            'page_title' => 'Mentorship',
            'page_description' => 'Connect with mentees and manage your mentoring sessions.',
            'page_icon' => 'heart-handshake',
        ]);
    }
}
