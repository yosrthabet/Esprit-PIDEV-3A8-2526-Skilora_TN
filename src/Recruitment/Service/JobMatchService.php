<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Entity\Skill;
use App\Entity\User;
use App\Recruitment\Entity\JobOffer;
use App\Repository\ProfileRepository;
use App\Repository\SkillRepository;

class JobMatchService
{
    /** @var array<int, list<string>> */
    private array $skillCache = [];

    public function __construct(
        private readonly ProfileRepository $profileRepository,
        private readonly SkillRepository $skillRepository,
    ) {
    }

    /** @return list<string> */
    public function getSkillNames(User $user): array
    {
        $userId = $user->getId();
        if ($userId !== null && isset($this->skillCache[$userId])) {
            return $this->skillCache[$userId];
        }

        $profile = $this->profileRepository->findOneBy(['user' => $user]);
        if ($profile === null) {
            return [];
        }

        $skills = array_values(array_filter(array_map(
            static fn (Skill $skill): string => mb_strtolower(trim($skill->getSkillName() ?? '')),
            $this->skillRepository->findBy(['profile' => $profile]),
        )));

        if ($userId !== null) {
            $this->skillCache[$userId] = $skills;
        }

        return $skills;
    }

    /**
     * @param list<string>|null $skillNames
     * @return array{score: int, confidence: string, reasons: list<string>}
     */
    public function score(User $user, JobOffer $jobOffer, ?array $skillNames = null): array
    {
        $skillNames ??= $this->getSkillNames($user);
        $haystack = mb_strtolower($jobOffer->getTitle() . ' ' . ($jobOffer->getDescription() ?? '') . ' ' . ($jobOffer->getSkillsRequired() ?? ''));
        $matched = [];

        foreach ($skillNames as $skillName) {
            if ($skillName !== '' && str_contains($haystack, $skillName)) {
                $matched[] = $skillName;
            }
        }

        $reasons = [];
        $score = $skillNames === [] ? 15 : 20;

        if ($skillNames === []) {
            $reasons[] = 'Low confidence: add skills to your profile for real fit scoring';
        } elseif ($matched !== []) {
            $skillRatio = count($matched) / max(1, count($skillNames));
            $score += (int) round(min(50, 50 * $skillRatio));
            $reasons[] = 'Matched skills: ' . implode(', ', array_slice($matched, 0, 4));
        } else {
            $reasons[] = 'No explicit skill overlap found';
        }

        $daysOld = $jobOffer->getPostedAt()->diff(new \DateTimeImmutable())->days;
        if ($daysOld <= 7) {
            $score += $skillNames === [] ? 5 : 10;
            $reasons[] = 'Fresh listing';
        }

        if ($jobOffer->getWorkType()?->value === 'remote') {
            $score += 5;
            $reasons[] = 'Remote-friendly';
        }

        if ($jobOffer->getSourceQuality() >= 70) {
            $score += $skillNames === [] ? 3 : 5;
            $reasons[] = 'High quality source';
        }

        $score = min(100, $score);
        $confidence = $skillNames === [] ? 'low confidence' : ($matched === [] ? 'weak fit' : 'profile match');

        return ['score' => $score, 'confidence' => $confidence, 'reasons' => array_values(array_unique($reasons))];
    }
}
