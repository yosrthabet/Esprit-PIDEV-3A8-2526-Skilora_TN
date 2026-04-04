<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Certificate;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\User;
use App\Enum\FormationLevel;
use App\Repository\CertificateRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FormationPublicController extends AbstractController
{
    public function __construct(
        private readonly FormationRepository $formationRepository,
        private readonly EnrollmentRepository $enrollmentRepository,
        private readonly CertificateRepository $certificateRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/formations', name: 'app_formation_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $enrolledIds = $user instanceof User
            ? $this->enrollmentRepository->findEnrolledFormationIds($user)
            : [];

        $q = $request->query->get('q');
        $cat = $request->query->get('cat');
        $lvl = $request->query->get('lvl');

        $formations = $this->formationRepository->findCatalogFiltered(
            \is_string($q) ? $q : null,
            \is_string($cat) ? $cat : null,
            \is_string($lvl) ? $lvl : null,
        );

        $levelOptions = [];
        foreach (FormationLevel::orderedCases() as $level) {
            $levelOptions[] = ['value' => $level->value, 'label' => $level->labelFr()];
        }

        return $this->render('formation/index.html.twig', [
            'formations' => $formations,
            'enrolledFormationIds' => $enrolledIds,
            'filter_q' => \is_string($q) ? $q : '',
            'filter_cat' => \is_string($cat) ? $cat : '',
            'filter_lvl' => \is_string($lvl) ? $lvl : '',
            'category_labels' => Formation::CATEGORY_LABELS_FR,
            'level_options' => $levelOptions,
        ]);
    }

    #[Route('/formations/{id}', name: 'app_formation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Formation $formation): Response
    {
        $user = $this->getUser();
        $enrollment = null;
        $certificate = null;
        if ($user instanceof User) {
            $enrollment = $this->enrollmentRepository->findOneByUserAndFormation($user, $formation);
            if (null !== $enrollment && $enrollment->isCompleted()) {
                $certificate = $this->certificateRepository->findOneBy(['user' => $user, 'formation' => $formation]);
            }
        }

        return $this->render('formation/show.html.twig', [
            'formation' => $formation,
            'enrollment' => $enrollment,
            'certificate' => $certificate,
        ]);
    }

    #[Route('/formations/{id}/inscription', name: 'app_formation_enroll', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function enroll(Request $request, Formation $formation): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $tokenId = 'formation_enroll_'.$formation->getId();
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid session. Please try again.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($this->enrollmentRepository->exists($user, $formation)) {
            $this->addFlash('info', 'Already enrolled.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        $enrollment = new Enrollment();
        $enrollment->setUser($user);
        $enrollment->setFormation($formation);
        $this->entityManager->persist($enrollment);
        $this->entityManager->flush();

        $this->addFlash('success', 'Successfully enrolled.');

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/formations/{id}/complete', name: 'app_formation_complete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function complete(Request $request, Formation $formation): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $tokenId = 'formation_complete_'.$formation->getId();
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid session. Please try again.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        $enrollment = $this->enrollmentRepository->findOneByUserAndFormation($user, $formation);
        if (null === $enrollment) {
            $this->addFlash('error', 'You must enroll before completing this formation.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        if ($enrollment->isCompleted()) {
            $this->addFlash('info', 'This formation is already marked as completed.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        $now = new \DateTimeImmutable();
        $enrollment->setIsCompleted(true);
        $enrollment->setCompletedAt($now);

        if (null === $this->certificateRepository->findOneBy(['user' => $user, 'formation' => $formation])) {
            $certificate = new Certificate();
            $certificate->setUser($user);
            $certificate->setFormation($formation);
            $certificate->setIssuedAt($now);
            $this->entityManager->persist($certificate);
        }

        $this->entityManager->flush();

        $this->addFlash('success', 'Formation completed.');

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
    }
}
