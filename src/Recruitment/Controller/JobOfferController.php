<?php

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\Entity\JobOffer;
use App\Recruitment\Form\JobOfferType;
use App\Recruitment\Entity\Company;
use App\Recruitment\Repository\CompanyRepository;
use App\Recruitment\Repository\JobOfferRepository;
use App\Recruitment\Service\EmployerContext;
use App\Recruitment\WorkTypeCatalog;
use App\Recruitment\Service\JobOfferManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employer', name: 'app_employer_')]
#[IsGranted('ROLE_EMPLOYER')]
final class JobOfferController extends AbstractController
{
    public function __construct(
        private readonly bool $employerSeesAllCandidatures = false,
    ) {
    }

    #[Route('/job-offers', name: 'job_offer_index', methods: ['GET'])]
    public function index(
        Request $request,
        EmployerContext $employerContext,
        CompanyRepository $companyRepository,
        JobOfferRepository $jobOfferRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $company = $employerContext->getCompanyForEmployer($user);

        $uid = $user->getId();
        $ownedCompanyIds = $uid !== null ? $companyRepository->findCompanyIdsByOwnerUserId((int) $uid) : [];
        /** @var list<Company> $ownedCompanies */
        $ownedCompanies = $companyRepository->findBy(['owner' => $user], ['id' => 'ASC']);
        $ownedNamesLower = array_values(array_unique(array_filter(array_map(
            static fn (Company $c) => mb_strtolower(trim((string) $c->getName())),
            $ownedCompanies,
        ))));

        $filter = (string) $request->query->get('filter', 'all');
        if (!\in_array($filter, ['all', 'open', 'closed', 'draft'], true)) {
            $filter = 'all';
        }
        $search = $request->query->get('q');
        $search = \is_string($search) ? trim($search) : null;
        if ($search === '') {
            $search = null;
        }

        $workTypeParam = $request->query->get('work_type');
        $workTypeParam = \is_string($workTypeParam) ? trim($workTypeParam) : '';
        $workType = WorkTypeCatalog::normalizeFilter($workTypeParam);

        $jobs = [];
        if ($this->employerSeesAllCandidatures) {
            $jobs = $jobOfferRepository->findAllForEmployerViewFiltered(
                $filter,
                $search,
                $workType,
            );
        } elseif ($ownedCompanyIds !== [] || $ownedNamesLower !== []) {
            $jobs = $jobOfferRepository->findAccessibleToEmployerFiltered(
                $ownedCompanyIds,
                $ownedNamesLower,
                $filter,
                $search,
                $workType,
            );
        }

        return $this->render('recrutement/employer/job_offer/index.html.twig', [
            'company' => $company ?? ($ownedCompanies[0] ?? null),
            'job_offers' => $jobs,
            'filter' => $filter,
            'search' => $search,
            'work_type' => $workTypeParam === '' ? 'all' : ($workType ?? 'all'),
            'employer_sees_all_offers' => $this->employerSeesAllCandidatures,
        ]);
    }

    #[Route('/job-offers/new', name: 'job_offer_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EmployerContext $employerContext,
        JobOfferManager $jobOfferManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $company = $employerContext->getCompanyForEmployer($user);

        if ($company === null) {
            $this->addFlash('warning', 'Aucune entreprise n’est liée à votre compte. Contactez le support.');

            return $this->redirectToRoute('app_employer_dashboard');
        }

        $jobOffer = new JobOffer();
        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jobOfferManager->createForEmployer($user, $jobOffer);
            $this->addFlash('success', 'Offre publiée avec succès.');

            return $this->redirectToRoute('app_employer_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        return $this->render('recrutement/employer/job_offer/form.html.twig', [
            'company' => $company,
            'form' => $form,
            'title' => 'Publier une offre',
            'submit_label' => 'Publier l’offre',
        ]);
    }

    #[Route('/job-offers/{id}', name: 'job_offer_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        JobOffer $jobOffer,
        JobOfferManager $jobOfferManager,
        CompanyRepository $companyRepository,
    ): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $canManage = $companyRepository->employerOwnsJobOfferDisplay($user, $jobOffer);
        if (!$canManage && !$this->employerSeesAllCandidatures) {
            $jobOfferManager->assertEmployerOwns($user, $jobOffer);
        }

        return $this->render('recrutement/employer/job_offer/show.html.twig', [
            'job_offer' => $jobOffer,
            'can_manage' => $canManage,
            'global_view_readonly' => !$canManage && $this->employerSeesAllCandidatures,
        ]);
    }

    #[Route('/job-offers/{id}/edit', name: 'job_offer_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(
        Request $request,
        JobOffer $jobOffer,
        JobOfferManager $jobOfferManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $jobOfferManager->assertEmployerOwns($user, $jobOffer);

        $form = $this->createForm(JobOfferType::class, $jobOffer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jobOfferManager->updateForEmployer($user, $jobOffer);
            $this->addFlash('success', 'Offre mise à jour.');

            return $this->redirectToRoute('app_employer_job_offer_show', ['id' => $jobOffer->getId()]);
        }

        return $this->render('recrutement/employer/job_offer/form.html.twig', [
            'company' => $jobOffer->getCompany(),
            'form' => $form,
            'title' => 'Modifier l’offre',
            'submit_label' => 'Enregistrer',
            'job_offer' => $jobOffer,
        ]);
    }

    #[Route('/job-offers/{id}/close', name: 'job_offer_close', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function close(
        Request $request,
        JobOffer $jobOffer,
        JobOfferManager $jobOfferManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $jobOfferManager->assertEmployerOwns($user, $jobOffer);

        if (!$this->isCsrfTokenValid('close_job_offer_'.$jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        if ($jobOffer->getStatus() !== 'OPEN') {
            $this->addFlash('warning', 'Cette offre est déjà fermée.');

            return $this->redirectToRoute('app_employer_job_offer_index');
        }

        $jobOfferManager->closeForEmployer($user, $jobOffer);
        $this->addFlash('success', 'Offre fermée.');

        return $this->redirectToRoute('app_employer_job_offer_index');
    }

    #[Route('/job-offers/{id}/delete', name: 'job_offer_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        Request $request,
        JobOffer $jobOffer,
        JobOfferManager $jobOfferManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $jobOfferManager->assertEmployerOwns($user, $jobOffer);

        if (!$this->isCsrfTokenValid('delete_job_offer_'.$jobOffer->getId(), $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $jobOfferManager->deleteForEmployer($user, $jobOffer);
        $this->addFlash('success', 'Offre supprimée.');

        return $this->redirectToRoute('app_employer_job_offer_index');
    }
}
