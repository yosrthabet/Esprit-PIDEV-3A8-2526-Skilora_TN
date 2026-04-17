<?php

namespace App\Recruitment\Service;

use App\Recruitment\Entity\JobOffer;

/**
 * Score de correspondance candidat / offre (à brancher sur profils, compétences, etc.).
 */
final class MatchingScoreService
{
    /**
     * @param array<string, mixed> $candidateContext Données candidat (profil, compétences…)
     */
    public function computeSimpleMatch(array $candidateContext, JobOffer $offer): float
    {
        return 0.0;
    }
}
