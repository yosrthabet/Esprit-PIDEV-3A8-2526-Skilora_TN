<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\FormationRepository;
use App\Repository\UserRepository;

/**
 * Agrège les indicateurs affichés sur le tableau de bord applicatif (données réelles, pas de chiffres factices).
 */
final readonly class DashboardDataProvider
{
    public function __construct(
        private UserRepository $userRepository,
        private FormationRepository $formationRepository,
    ) {
    }

    /**
     * @return array{stats: list<array{label: string, value: string, change: string, trend: 'up'|'down'}>, recent_users: list<array{name: string, email: string, role: string, status: string}>}
     */
    public function getOverview(): array
    {
        $userCount = $this->userRepository->countAll();
        $formationCount = $this->formationRepository->countAll();
        $activeUsers = $this->userRepository->countActiveAccounts();
        $verifiedUsers = $this->userRepository->countVerifiedAccounts();

        return [
            'stats' => [
                [
                    'label' => 'Utilisateurs',
                    'value' => $this->formatInt($userCount),
                    'change' => 'Comptes enregistrés',
                    'trend' => 'up',
                ],
                [
                    'label' => 'Formations',
                    'value' => $this->formatInt($formationCount),
                    'change' => 'Fiches catalogue',
                    'trend' => 'up',
                ],
                [
                    'label' => 'Comptes actifs',
                    'value' => $this->formatInt($activeUsers),
                    'change' => 'Accès autorisé',
                    'trend' => 'up',
                ],
                [
                    'label' => 'Profils vérifiés',
                    'value' => $this->formatInt($verifiedUsers),
                    'change' => 'E-mail confirmé',
                    'trend' => $verifiedUsers > 0 ? 'up' : 'down',
                ],
            ],
            'recent_users' => $this->userRepository->findRecentSummaries(8),
        ];
    }

    private function formatInt(int $n): string
    {
        return number_format($n, 0, ',', "\u{202f}");
    }
}
