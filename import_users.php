<?php
/**
 * Database import script - Updates users with bcrypt hashed passwords
 * Run this after importing the SQL file
 */

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/config/bootstrap.php';

use Doctrine\ORM\EntityManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

// Get services
$container = require_once __DIR__.'/var/bootstrap.php.cache';
$em = $container->get('doctrine')->getManager();
$passwordHasher = $container->get(UserPasswordHasher::class);

// Update admin user
$sql = "UPDATE users SET password = ? WHERE username = ?";
$connection = $em->getConnection();

// Hash passwords using bcrypt
$adminHash = password_hash('admin123', PASSWORD_BCRYPT);
$userHash = password_hash('user123', PASSWORD_BCRYPT);
$employerHash = password_hash('emp123', PASSWORD_BCRYPT);

// Execute updates
$connection->executeStatement($sql, [$adminHash, 'admin']);
$connection->executeStatement($sql, [$userHash, 'user']);
$connection->executeStatement($sql, [$employerHash, 'employer']);

echo "✅ Passwords updated successfully!\n";
echo "Admin: admin / admin123\n";
echo "User: user / user123\n";
echo "Employer: employer / emp123\n";
