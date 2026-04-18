<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Formation;
use App\Repository\ReviewRepository;

/**
 * Aggregated rating analytics for a single formation (no N+1; uses repository aggregates).
 */
final class ReviewService
{
    public function __construct(
        private readonly ReviewRepository $reviewRepository,
    ) {
    }

    /**
     * @return array{
     *   average: float,
     *   total: int,
     *   likePercentage: float,
     *   distribution: array{5: int, 4: int, 3: int, 2: int, 1: int}
     * }
     */
    public function getFormationStats(Formation $formation): array
    {
        $distribution = $this->reviewRepository->getRatingDistribution($formation);

        $total = 0;
        $weightedSum = 0;
        foreach ($distribution as $stars => $count) {
            $total += $count;
            $weightedSum += $stars * $count;
        }

        $average = $total > 0 ? round($weightedSum / $total, 2) : 0.0;
        $likes = ($distribution[4] ?? 0) + ($distribution[5] ?? 0);
        $likePercentage = $total > 0 ? round(100.0 * $likes / $total, 1) : 0.0;

        return [
            'average' => $average,
            'total' => $total,
            'likePercentage' => $likePercentage,
            'distribution' => [
                5 => $distribution[5],
                4 => $distribution[4],
                3 => $distribution[3],
                2 => $distribution[2],
                1 => $distribution[1],
            ],
        ];
    }
}
