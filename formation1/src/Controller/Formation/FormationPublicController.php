<?php

declare(strict_types=1);

namespace App\Controller\Formation;

use App\Entity\Certificate;
use App\Entity\Enrollment;
use App\Entity\Formation;
use App\Entity\FormationReview;
use App\Entity\ReviewLike;
use App\Entity\User;
use App\Enum\FormationLevel;
use App\Repository\CertificateRepository;
use App\Repository\EnrollmentRepository;
use App\Repository\FormationRepository;
use App\Repository\ReviewLikeRepository;
use App\Repository\ReviewRepository;
use App\Service\ReviewService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        private readonly ReviewRepository $reviewRepository,
        private readonly ReviewLikeRepository $reviewLikeRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReviewService $reviewService,
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

        $reviews = $this->reviewRepository->findLatestByFormation($formation);
        $reviewIds = array_map(static fn (FormationReview $review): int => (int) $review->getId(), $reviews);
        $reviewVotes = $user instanceof User ? $this->reviewLikeRepository->getUserVotesForReviewIds($user, $reviewIds) : [];

        return $this->render('formation/show.html.twig', [
            'formation' => $formation,
            'enrollment' => $enrollment,
            'certificate' => $certificate,
            'review_stats' => $this->reviewService->getFormationStats($formation),
            'reviews' => $reviews,
            'my_review' => $user instanceof User ? $this->reviewRepository->findOneByFormationAndUser($formation, $user) : null,
            'review_votes' => $reviewVotes,
        ]);
    }

    #[Route('/formations/{id}/avis', name: 'app_formation_review_submit', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function submitReview(Request $request, Formation $formation): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $tokenId = 'formation_review_'.$formation->getId();
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid session. Please try again.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        $enrollment = $this->enrollmentRepository->findOneByUserAndFormation($user, $formation);
        if (null === $enrollment || !$enrollment->isCompleted()) {
            $this->addFlash('error', 'You can review this formation only after completion.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        $rating = (int) $request->request->get('rating', 0);
        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', 'Please choose a rating between 1 and 5 stars.');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        $commentRaw = trim((string) $request->request->get('comment', ''));
        $comment = '' === $commentRaw ? null : $commentRaw;
        if (null !== $comment && mb_strlen($comment) > 1500) {
            $this->addFlash('error', 'Your review is too long (max 1500 characters).');

            return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
        }

        $review = $this->reviewRepository->findOneByFormationAndUser($formation, $user);
        $isNewReview = null === $review;
        if ($isNewReview) {
            $review = new FormationReview();
            $review->setFormation($formation);
            $review->setUser($user);
            $this->entityManager->persist($review);
        }

        $review->setRating($rating);
        $review->setComment($comment);
        $this->entityManager->flush();

        $this->addFlash('success', $isNewReview ? 'Review submitted.' : 'Review updated.');

        return $this->redirectToRoute('app_formation_show', ['id' => $formation->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/formations/reviews/{id}/vote', name: 'app_formation_review_vote', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function voteReview(Request $request, int $id): JsonResponse
    {
        $review = $this->reviewRepository->find($id);
        if (!$review instanceof FormationReview) {
            return new JsonResponse(['success' => false, 'message' => 'Review not found.'], Response::HTTP_NOT_FOUND);
        }

        $tokenId = 'formation_review_vote_'.$review->getId();
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid CSRF token.'], Response::HTTP_FORBIDDEN);
        }

        $vote = (string) $request->request->get('vote');
        $voteValue = match ($vote) {
            'helpful' => ReviewLike::VOTE_HELPFUL,
            'not_helpful' => ReviewLike::VOTE_NOT_HELPFUL,
            default => null,
        };

        if (null === $voteValue) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid vote value.'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $existing = $this->reviewLikeRepository->findOneByReviewAndUser($review, $user);
        if (null === $existing) {
            $existing = new ReviewLike();
            $existing->setReview($review);
            $existing->setUser($user);
            $existing->setVote($voteValue);
            $this->entityManager->persist($existing);
            if (ReviewLike::VOTE_HELPFUL === $voteValue) {
                $review->incrementUsefulCount();
            } else {
                $review->incrementNotUsefulCount();
            }
        } elseif ($existing->getVote() !== $voteValue) {
            if (ReviewLike::VOTE_HELPFUL === $existing->getVote()) {
                $review->setUsefulCount($review->getUsefulCount() - 1);
            } else {
                $review->setNotUsefulCount($review->getNotUsefulCount() - 1);
            }
            $existing->setVote($voteValue);
            if (ReviewLike::VOTE_HELPFUL === $voteValue) {
                $review->incrementUsefulCount();
            } else {
                $review->incrementNotUsefulCount();
            }
        } else {
            // Same vote already registered by this user; keep idempotent.
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'usefulCount' => $review->getUsefulCount(),
            'notUsefulCount' => $review->getNotUsefulCount(),
            'helpfulnessPercentage' => $review->getHelpfulnessPercentage(),
            'userVote' => $voteValue,
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
