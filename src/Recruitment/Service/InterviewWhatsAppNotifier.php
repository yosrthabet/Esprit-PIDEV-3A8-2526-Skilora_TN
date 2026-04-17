<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use App\Recruitment\Entity\JobInterview;
use App\Service\Finance\TwilioWhatsAppNotifier;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;

/**
 * Envoi WhatsApp candidat apres planification d’entretien.
 */
final class InterviewWhatsAppNotifier
{
    /** @var array<string, true>|null */
    private ?array $usersColumns = null;
    /** @var array<string, true>|null */
    private ?array $profilesColumns = null;

    public function __construct(
        private readonly Connection $connection,
        private readonly TwilioWhatsAppNotifier $twilioWhatsAppNotifier,
        private readonly InterviewWhatsAppMessageFactory $messageFactory,
        private readonly LoggerInterface $logger,
        private readonly string $defaultCountryDialCode = '+216',
    ) {
    }

    public function notifyCandidateInterviewScheduled(JobInterview $interview): InterviewWhatsAppDispatchResult
    {
        $application = $interview->getApplication();
        $candidate = $application?->getCandidate();
        $jobOffer = $application?->getJobOffer();
        if ($application === null || $candidate === null || $jobOffer === null) {
            return new InterviewWhatsAppDispatchResult('skipped', 'Données candidature/offre manquantes.');
        }

        $candidateId = $candidate->getId();
        if ($candidateId === null) {
            return new InterviewWhatsAppDispatchResult('skipped', 'Candidat sans identifiant.');
        }

        $phone = $this->resolveCandidatePhone((int) $candidateId);
        if ($phone === null) {
            $this->logger->info('Recruitment interview WhatsApp skipped: no candidate phone', [
                'application_id' => $application->getId(),
                'candidate_id' => $candidateId,
            ]);
            return new InterviewWhatsAppDispatchResult('skipped', 'Numéro candidat introuvable.');
        }

        $candidateName = trim((string) ($candidate->getFullName() ?? ''));
        if ($candidateName === '') {
            $candidateName = trim((string) ($candidate->getUsername() ?? ''));
        }
        if ($candidateName === '') {
            $candidateName = trim((string) ($candidate->getEmail() ?? 'Candidat'));
        }

        $jobTitle = trim((string) ($jobOffer->getTitle() ?? ''));
        if ($jobTitle === '') {
            $jobTitle = 'Offre #'.(string) ($jobOffer->getId() ?? '');
        }

        $normalizedPhone = $this->normalizePhoneForTwilio($phone);
        if ($normalizedPhone === null) {
            $this->logger->warning('Recruitment interview WhatsApp skipped: invalid phone format', [
                'application_id' => $application->getId(),
                'candidate_id' => $candidateId,
                'raw_phone' => $phone,
            ]);

            return new InterviewWhatsAppDispatchResult('skipped', 'Numéro invalide.', $phone);
        }

        $message = $this->messageFactory->build(
            $candidateName,
            $jobTitle,
            $interview,
            $interview->getNotes(),
        );

        try {
            $this->twilioWhatsAppNotifier->sendWhatsAppAndOptionalSms($normalizedPhone, $message);
            return new InterviewWhatsAppDispatchResult('sent', null, $normalizedPhone);
        } catch (\Throwable $e) {
            $this->logger->warning('Recruitment interview WhatsApp notification failed', [
                'application_id' => $application->getId(),
                'candidate_id' => $candidateId,
                'phone' => $normalizedPhone,
                'error' => $e->getMessage(),
            ]);
            return new InterviewWhatsAppDispatchResult('failed', $e->getMessage(), $normalizedPhone);
        }
    }

    private function resolveCandidatePhone(int $candidateUserId): ?string
    {
        $phoneCol = $this->pickUsersPhoneColumn();
        if ($phoneCol !== null) {
            $val = $this->connection->fetchOne(
                'SELECT '.$phoneCol.' FROM users WHERE id = ?',
                [$candidateUserId],
            );
            if (\is_string($val) || \is_numeric($val)) {
                $phone = trim((string) $val);
                if ($phone !== '') {
                    return $phone;
                }
            }
        }

        $profilePhoneCol = $this->pickProfilesPhoneColumn();
        if ($profilePhoneCol === null) {
            return null;
        }
        $val = $this->connection->fetchOne(
            'SELECT '.$profilePhoneCol.' FROM profiles WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [$candidateUserId],
        );
        if (!\is_string($val) && !\is_numeric($val)) {
            return null;
        }
        $phone = trim((string) $val);

        return $phone !== '' ? $phone : null;
    }

    private function pickUsersPhoneColumn(): ?string
    {
        if ($this->usersColumns === null) {
            $this->usersColumns = [];
            if (!$this->connection->createSchemaManager()->tablesExist(['users'])) {
                return null;
            }
            foreach ($this->connection->createSchemaManager()->listTableColumns('users') as $col) {
                $this->usersColumns[strtolower($col->getName())] = true;
            }
        }

        foreach (['phone', 'phone_number', 'mobile', 'telephone'] as $name) {
            if (isset($this->usersColumns[$name])) {
                return $name;
            }
        }

        return null;
    }

    private function pickProfilesPhoneColumn(): ?string
    {
        if ($this->profilesColumns === null) {
            $this->profilesColumns = [];
            if (!$this->connection->createSchemaManager()->tablesExist(['profiles'])) {
                return null;
            }
            foreach ($this->connection->createSchemaManager()->listTableColumns('profiles') as $col) {
                $this->profilesColumns[strtolower($col->getName())] = true;
            }
        }

        foreach (['phone', 'phone_number', 'mobile', 'telephone'] as $name) {
            if (isset($this->profilesColumns[$name])) {
                return $name;
            }
        }

        return null;
    }

    private function normalizePhoneForTwilio(string $raw): ?string
    {
        $value = trim($raw);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'whatsapp:')) {
            $value = trim(substr($value, 9));
        }

        if (str_starts_with($value, '00')) {
            $value = '+'.substr($value, 2);
        }

        if (str_starts_with($value, '+')) {
            $digits = preg_replace('/\D+/', '', substr($value, 1)) ?? '';

            return $digits !== '' ? '+'.$digits : null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        $cc = trim($this->defaultCountryDialCode);
        if ($cc === '' || $cc[0] !== '+') {
            $cc = '+216';
        }
        $ccDigits = preg_replace('/\D+/', '', substr($cc, 1)) ?? '216';
        $local = ltrim($digits, '0');

        return '+'.$ccDigits.$local;
    }
}

