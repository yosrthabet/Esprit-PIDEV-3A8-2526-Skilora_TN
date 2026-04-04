<?php

/**
 * Store a bcrypt hash for a user (legacy DB often has plaintext passwords from Java).
 *
 * Usage: php bin/fix_legacy_password.php emp@gmail.com yourPassword
 */
require dirname(__DIR__).'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

if ($argc < 3) {
    fwrite(STDERR, "Usage: php bin/fix_legacy_password.php email@example.com plainPassword\n");
    exit(1);
}

[, $email, $plain] = $argv;

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool) ($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();
$container = $kernel->getContainer();

$conn = $container->get('doctrine.dbal.default_connection');
$hash = password_hash($plain, \PASSWORD_BCRYPT, ['cost' => 13]);

$n = $conn->executeStatement(
    'UPDATE users SET password = ? WHERE email = ?',
    [$hash, $email],
);

if ($n === 0) {
    fwrite(STDERR, "No user updated. Check email.\n");
    exit(1);
}

echo "Password hash updated for {$email}.\n";
