<?php

declare(strict_types=1);

namespace App\Finance\Service;

use App\Entity\User;
use App\Finance\Entity\Contract;
use App\Finance\Entity\JobReview;
use App\Finance\Repository\JobReviewRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReviewService
{
    public function __construct(
        private readonly JobReviewRepository $reviewRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function hasReviewed(Contract $contract, User $reviewer): bool
    {
        return $this->reviewRepository->findOneForContractAndReviewer($contract, $reviewer) !== null;
    }

    public function submitReview(
        Contract $contract,
        User $reviewer,
        User $reviewedUser,
        int $rating,
        ?string $comment = null,
        ?int $communicationRating = null,
        ?int $qualityRating = null,
        ?int $timelinessRating = null,
    ): JobReview {
        $existing = $this->reviewRepository->findOneForContractAndReviewer($contract, $reviewer);
        if ($existing !== null) {
            throw new \LogicException('You have already reviewed this contract.');
        }

        $review = (new JobReview())
            ->setContract($contract)
            ->setReviewer($reviewer)
            ->setReviewedUser($reviewedUser)
            ->setRating($rating)
            ->setComment($comment);

        if ($communicationRating !== null) {
            $review->setCommunicationRating($communicationRating);
        }
        if ($qualityRating !== null) {
            $review->setQualityRating($qualityRating);
        }
        if ($timelinessRating !== null) {
            $review->setTimelinessRating($timelinessRating);
        }

        $this->entityManager->persist($review);
        $this->entityManager->flush();

        return $review;
    }

    /**
     * @return array{average: float|null, count: int, reviews: list<JobReview>}
     */
    public function profileSummary(User $user): array
    {
        return [
            'average' => $this->reviewRepository->averageRatingForUser($user),
            'count' => $this->reviewRepository->countForUser($user),
            'reviews' => $this->reviewRepository->findForUser($user),
        ];
    }
}
