<?php

declare(strict_types=1);

namespace App\Recruitment\Controller;

use App\Entity\User;
use App\Recruitment\ApplicationStatus;
use App\Recruitment\Form\InterviewScheduleType;
use App\Recruitment\Repository\ApplicationsTableGateway;
use App\Recruitment\Repository\JobOfferRepository;
use App\Recruitment\Repository\JobInterviewRepository;
use App\Recruitment\Repository\CompanyRepository;
use App\Repository\UserRepository;
use App\Recruitment\InterviewFormat;
use App\Recruitment\Service\EmployerContext;
use App\Recruitment\Service\EmployerInterviewService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employer', name: 'app_employer_')]
#[IsGranted('ROLE_EMPLOYER')]
final class EmployerInterviewsController extends AbstractController
{
    public function __construct(
        private readonly bool $employerSeesAllCandidatures = false,
    ) {
    }

    #[Route('/entretiens', name: 'interviews', methods: ['GET'])]
    public function index(
        EmployerContext $employerContext,
        UserRepository $userRepository,
        JobInterviewRepository $jobInterviewRepository,
    ): Response {
        $principal = $this->getUser();
        if (!$principal instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $user = $userRepository->find($principal->getId()) ?? $principal;
        $company = $employerContext->getCompanyForEmployer($user);

        $uid = $user->getId();
        if ($uid === null) {
            throw $this->createAccessDeniedException();
        }

        $interviewRows = $jobInterviewRepository->findEmployerInterviewListRows(
            (int) $uid,
            $this->employerSeesAllCandidatures,
        );

        return $this->render('recrutement/employer/interview/index.html.twig', [
            'company' => $company,
            'interview_rows' => $interviewRows,
        ]);
    }

    #[Route('/entretiens/planifier/{id}', name: 'interviews_plan', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function plan(
        Request $request,
        int $id,
        CompanyRepository $companyRepository,
        ApplicationsTableGateway $applicationsTableGateway,
        JobOfferRepository $jobOfferRepository,
        JobInterviewRepository $jobInterviewRepository,
        EmployerInterviewService $employerInterviewService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $uid = $user->getId();
        if ($uid === null) {
            throw $this->createAccessDeniedException();
        }

        $candidature = $applicationsTableGateway->fetchEmployerCandidatureProfileForEmployer($id, (int) $uid);
        if ($candidature === null) {
            throw $this->createNotFoundException('Candidature introuvable.');
        }

        $job = $jobOfferRepository->find($candidature->jobOfferId);
        if ($job === null) {
            throw $this->createNotFoundException('Offre introuvable.');
        }
        if (!$this->employerSeesAllCandidatures && !$companyRepository->employerOwnsJobOfferDisplay($user, $job)) {
            throw $this->createAccessDeniedException();
        }

        if (strtoupper(trim($candidature->statusRaw)) !== ApplicationStatus::ACCEPTED) {
            $this->addFlash('warning', 'Seules les candidatures acceptées permettent de planifier un entretien.');

            return $this->redirectToRoute('app_employer_interviews');
        }

        $interview = $jobInterviewRepository->findOneByApplicationId($id);
        $scheduled = $interview?->getScheduledAt();
        $defaultData = [
            'interviewDate' => $scheduled,
            'interviewTime' => $scheduled,
            'format' => $interview?->getFormat() ?? InterviewFormat::ONLINE,
            'location' => $interview?->getLocation() ?? $job->getLocation() ?? '',
            'notes' => $interview?->getNotes() ?? '',
        ];

        $form = $this->createForm(InterviewScheduleType::class, $defaultData);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateRaw = $form->get('interviewDate')->getData();
            $timeRaw = $form->get('interviewTime')->getData();
            $format = (string) $form->get('format')->getData();
            $locationRaw = $form->get('location')->getData();
            $location = \is_string($locationRaw) ? trim($locationRaw) : null;
            if ($location === '') {
                $location = null;
            }
            $notesRaw = $form->get('notes')->getData();
            $notes = \is_string($notesRaw) && trim($notesRaw) !== '' ? trim($notesRaw) : null;

            if (!$dateRaw instanceof \DateTimeImmutable || !$timeRaw instanceof \DateTimeImmutable) {
                $this->addFlash('error', 'Date et heure invalides.');

                return $this->render('recrutement/employer/interview/form.html.twig', [
                    'candidature' => $candidature,
                    'job_offer' => $job,
                    'form' => $form,
                ]);
            }

            $scheduledAt = $dateRaw->setTime(
                (int) $timeRaw->format('H'),
                (int) $timeRaw->format('i'),
                (int) $timeRaw->format('s'),
            );

            try {
                $scheduleResult = $employerInterviewService->scheduleOrUpdate(
                    $user,
                    $id,
                    $scheduledAt,
                    $format,
                    $location,
                    $notes,
                );
                $whatsApp = $scheduleResult->whatsApp;
                $waStatus = match ($whatsApp->status) {
                    'sent' => sprintf(
                        ' WhatsApp envoyé%s.',
                        $whatsApp->recipient !== null ? ' vers '.$whatsApp->recipient : '',
                    ),
                    'skipped' => ' WhatsApp non envoyé: '.($whatsApp->detail ?? 'raison inconnue').'.',
                    default => ' WhatsApp échoué: '.($whatsApp->detail ?? 'erreur inconnue').'.',
                };
                $this->addFlash(
                    'success',
                    sprintf(
                        'Entretien enregistré le %s%s.%s',
                        $scheduledAt->format('d/m/Y à H:i'),
                        $location !== null ? ' · '.$location : '',
                        $waStatus,
                    ),
                );
            } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
                $this->addFlash('error', $e->getMessage());
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Enregistrement impossible : '.$e->getMessage());
            }

            return $this->redirectToRoute('app_employer_interviews');
        }

        return $this->render('recrutement/employer/interview/form.html.twig', [
            'candidature' => $candidature,
            'job_offer' => $job,
            'form' => $form,
        ]);
    }
}
