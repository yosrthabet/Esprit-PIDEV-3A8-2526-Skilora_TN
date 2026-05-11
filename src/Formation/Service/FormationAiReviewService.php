<?php

declare(strict_types=1);

namespace App\Formation\Service;

use App\Formation\Entity\Formation;
use App\Formation\FormationStatus;
use App\Service\AI\SkiloraMlClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class FormationAiReviewService
{
    private const CONFIDENCE_THRESHOLD = 0.70;

    public function __construct(
        private readonly SkiloraMlClient $mlClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly FormationNotifier $notifier,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function reviewFormation(Formation $formation): void
    {
        if ($formation->getStatus() !== FormationStatus::PENDING_REVIEW) {
            return;
        }

        $payload = $this->buildReviewPayload($formation);
        $result = $this->mlClient->reviewFormation($payload);

        if (isset($result['error'])) {
            $this->logger->warning('AI review failed for formation #{id}, falling back to manual.', [
                'id' => $formation->getId(),
                'error' => $result['error'],
            ]);
            return;
        }

        $rawScore = $result['confidence_score'] ?? $result['score'] ?? null;
        $score = is_numeric($rawScore) ? (float) $rawScore : 0.0;
        $decision = is_string($result['decision'] ?? null) ? $result['decision'] : ($score >= self::CONFIDENCE_THRESHOLD ? 'approve' : 'refuse');
        $reason = is_string($result['reason'] ?? null) ? $result['reason'] : (is_string($result['note'] ?? null) ? $result['note'] : null);

        $formation->setReviewScore((string) round($score, 2));

        if ($decision === 'approve') {
            $formation->setStatus(FormationStatus::PUBLISHED);
            $formation->setReviewNote(null);
            $this->entityManager->flush();
            $this->notifier->notifyTrainerFormationPublished($formation);
            $this->logger->info('Formation #{id} auto-published with score {score}', [
                'id' => $formation->getId(),
                'score' => $score,
            ]);
        } else {
            $formation->setStatus(FormationStatus::REFUSED);
            $formation->setReviewNote($reason ?: 'Content quality score below threshold (' . round($score * 100) . '%). Please improve and resubmit.');
            $this->entityManager->flush();
            $this->notifier->notifyTrainerFormationRefused($formation);
            $this->logger->info('Formation #{id} refused with score {score}', [
                'id' => $formation->getId(),
                'score' => $score,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReviewPayload(Formation $formation): array
    {
        $modules = [];
        foreach ($formation->getModules() as $module) {
            $materials = [];
            foreach ($module->getMaterials() as $material) {
                $materials[] = [
                    'title' => $material->getTitle(),
                    'kind' => $material->getKind(),
                    'url' => $material->getResourceUrl(),
                ];
            }
            $modules[] = [
                'title' => $module->getTitle(),
                'description' => $module->getDescription(),
                'duration_minutes' => $module->getDurationMinutes(),
                'materials' => $materials,
            ];
        }

        return [
            'formation_id' => $formation->getId(),
            'title' => $formation->getTitle(),
            'description' => $formation->getDescription(),
            'category' => $formation->getCategory(),
            'level' => $formation->getLevel()->value,
            'duration_hours' => $formation->getDurationHours(),
            'modules' => $modules,
            'module_count' => count($modules),
            'has_signature' => $formation->getCertificateSignatureFilename() !== null,
        ];
    }
}
