<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\Profile;
use App\Entity\User;
use App\Repository\ProfileRepository;
use App\Repository\SkillRepository;
use Doctrine\DBAL\Exception\TableNotFoundException;

final class ProfileCompletionService
{
    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly SkillRepository $skillRepository,
    ) {
    }

    /**
     * @return array{percentage: int, steps: list<array{label: string, done: bool, route: string, icon: string}>}
     */
    public function compute(User $user, ?Profile $profile = null, ?int $skillCount = null): array
    {
        $profile ??= $this->profileRepository->findOneBy(['user' => $user]);

        $steps = [
            [
                'label' => 'Ajouter votre nom complet',
                'done' => $this->filled($profile?->getFirstName()) && $this->filled($profile?->getLastName()),
                'route' => 'app_profile',
                'icon' => 'user',
            ],
            [
                'label' => 'Ajouter une photo de profil',
                'done' => $this->filled($user->getPhotoUrl()) || $this->filled($profile?->getPhotoUrl()),
                'route' => 'app_profile',
                'icon' => 'camera',
            ],
            [
                'label' => 'Ajouter un titre professionnel',
                'done' => $this->filled($profile?->getHeadline()),
                'route' => 'app_profile',
                'icon' => 'briefcase',
            ],
            [
                'label' => 'Écrire votre bio',
                'done' => $this->filled($profile?->getBio()),
                'route' => 'app_profile',
                'icon' => 'file-text',
            ],
            [
                'label' => 'Ajouter votre téléphone',
                'done' => $this->filled($profile?->getPhone()),
                'route' => 'app_profile',
                'icon' => 'phone',
            ],
            [
                'label' => 'Ajouter votre localisation',
                'done' => $this->filled($profile?->getLocation()),
                'route' => 'app_profile',
                'icon' => 'map-pin',
            ],
            [
                'label' => 'Vérifier votre email',
                'done' => $user->isVerified(),
                'route' => 'app_settings',
                'icon' => 'mail-check',
            ],
            [
                'label' => 'Ajouter au moins 1 compétence',
                'done' => $skillCount !== null ? $skillCount > 0 : $this->hasSkills($profile),
                'route' => 'app_profile',
                'icon' => 'zap',
            ],
        ];

        $completed = count(array_filter($steps, static fn (array $s): bool => $s['done']));
        $total = count($steps);
        $percentage = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'percentage' => $percentage,
            'steps' => $steps,
        ];
    }

    private function filled(?string $value): bool
    {
        return $value !== null && trim($value) !== '';
    }

    private function hasSkills(?Profile $profile): bool
    {
        if ($profile === null || $profile->getId() === null) {
            return false;
        }

        try {
            return $this->skillRepository->count(['profile' => $profile]) > 0;
        } catch (TableNotFoundException) {
            return false;
        }
    }
}
