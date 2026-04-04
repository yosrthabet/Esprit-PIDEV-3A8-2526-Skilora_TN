<?php

declare(strict_types=1);

namespace App\Recruitment\Service;

use Doctrine\DBAL\Connection;

/**
 * Table SQL legacy `profiles` (user_id → users.id) — requis par `applications.candidate_profile_id` sur certaines bases.
 */
final class CandidateProfileIdResolver
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function findProfileIdForUserId(int $userId): ?int
    {
        try {
            $v = $this->connection->fetchOne('SELECT id FROM profiles WHERE user_id = ? LIMIT 1', [$userId]);
        } catch (\Throwable) {
            return null;
        }

        return $v === false ? null : (int) $v;
    }

    /**
     * Compte utilisateur (users.id) lié à une ligne {@code profiles} — doit être celui stocké dans {@code applications.candidate_id}.
     */
    public function findUserIdForProfileId(int $profileId): ?int
    {
        try {
            $v = $this->connection->fetchOne('SELECT user_id FROM profiles WHERE id = ? LIMIT 1', [$profileId]);
        } catch (\Throwable) {
            return null;
        }

        return $v === false || $v === null ? null : (int) $v;
    }
}
