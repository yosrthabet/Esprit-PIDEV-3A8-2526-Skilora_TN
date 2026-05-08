# Skilora TN

Skilora TN is a Symfony 6.4 web application focused on user authentication, account security, profile management, notifications, and role-based workspaces.

This repository currently contains the streamlined web app. Earlier recruitment, finance, formation, community, and support modules are not part of the active codebase and are represented only by placeholder/redirect routes where needed.

## Tech Stack

| Layer | Technology |
| --- | --- |
| Backend | Symfony 6.4 |
| Language | PHP 8.2+ |
| Database | MariaDB/MySQL |
| ORM | Doctrine |
| Frontend | Twig, Tailwind CSS, Alpine.js |
| Assets | Vite |
| Tests | PHPUnit |

## Active Features

- Email/password authentication with email verification
- OAuth login/registration flow for supported providers
- Passkey registration and authentication
- Two-factor authentication settings
- Password reset and account settings
- Profile editing with skills, experience, portfolio, and avatar upload
- AI-assisted profile analysis
- Notifications
- Role-based dashboard/workspace pages
- Public marketing pages: about, pricing, careers, case studies, offline

## Getting Started

### Prerequisites

| Tool | Version |
| --- | --- |
| PHP | 8.2+ |
| Composer | Latest |
| Node.js | 18+ |
| MariaDB/MySQL | 10.4+ |

### Installation

```bash
composer install
npm install
cp .env .env.local
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
npm run build
symfony server:start
```

Configure `.env.local` with the local database URL and any OAuth/API credentials required for your environment.

## Common Commands

```bash
npm run build
php bin/phpunit
php bin/console lint:container
php bin/console lint:twig templates
php bin/console doctrine:schema:validate --skip-sync
vendor/bin/phpstan analyse --no-progress
```

## Project Layout

```text
src/
  Command/             Console commands
  Controller/          Route handlers for auth, dashboard, pages, profile, settings, notifications
  Entity/              Doctrine entities
  EventSubscriber/     HTTP/security/database subscribers
  Repository/          Doctrine repositories
  Security/            Authenticators and security handlers
  Service/             Application services
  Twig/                Twig extensions
  Validator/           Custom validators
templates/             Twig templates and UI components
assets/                Frontend source files
public/                Web root and built assets
config/                Symfony configuration
migrations/            Doctrine migrations
tests/                 PHPUnit tests
```

## Status

This is an academic project/prototype. Keep README features aligned with the active routes and tests when adding or removing modules.
