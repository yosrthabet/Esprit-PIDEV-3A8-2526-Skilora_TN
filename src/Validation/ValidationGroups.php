<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Central validation group names (Symfony Validator).
 * Use with #[Assert\...] groups: [self::FORMATION_CREATE, ...] and form validation_groups option.
 */
final class ValidationGroups
{
    public const FORMATION_CREATE = 'formation_create';

    public const FORMATION_UPDATE = 'formation_update';

    /**
     * @return list<string>
     */
    public static function formationWrite(): array
    {
        return [self::FORMATION_CREATE, self::FORMATION_UPDATE];
    }

    private function __construct()
    {
    }
}
