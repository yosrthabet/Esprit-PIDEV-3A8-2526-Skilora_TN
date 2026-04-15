<?php

namespace App\Security;

use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

/**
 * Verifies bcrypt/argon hashes and legacy clear-text passwords from older Skilora data.
 * New passwords are hashed with the native (bcrypt) hasher.
 */
final class SkiloraPasswordHasher implements PasswordHasherInterface
{
    private NativePasswordHasher $native;

    public function __construct()
    {
        $this->native = new NativePasswordHasher();
    }

    public function hash(#[\SensitiveParameter] string $plainPassword): string
    {
        return $this->native->hash($plainPassword);
    }

    public function verify(string $hashedPassword, #[\SensitiveParameter] string $plainPassword): bool
    {
        if ($hashedPassword === '') {
            return false;
        }
        if (str_starts_with($hashedPassword, '$')) {
            return $this->native->verify($hashedPassword, $plainPassword);
        }

        return hash_equals($hashedPassword, $plainPassword);
    }

    public function needsRehash(string $hashedPassword): bool
    {
        if (!str_starts_with($hashedPassword, '$')) {
            return true;
        }

        return $this->native->needsRehash($hashedPassword);
    }
}
