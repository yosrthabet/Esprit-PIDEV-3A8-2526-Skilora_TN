<div align="center">

# 🌟 Skilora TN

### *Tunisia's All-in-One Talent & Career Ecosystem*

[![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?style=for-the-badge&logo=symfony&logoColor=white)](https://symfony.com/)
[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Tailwind](https://img.shields.io/badge/Tailwind-3.x-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-Academic-green?style=for-the-badge)](LICENSE)

---

**Connecting Tunisia's Youth to Global Opportunities.**  
*Secure · Intelligent · Limitless*

</div>

---

## 👋 Welcome to Skilora

**Skilora** is a modern web platform built with Symfony, designed for the Tunisian job market. It connects talents, companies, and training opportunities into one unified ecosystem.

Whether you're:
- 🎓 A student
- 💼 A job seeker
- 🏢 A company

👉 Skilora gives you everything in one place.

> Built with love in Tunisia 🇹🇳 by ESPRIT engineering students.

---

## 🎯 What Is Skilora?

Skilora is a **talent recruitment and career development platform** that covers:

- 🔍 Job search & applications
- 📅 Interview management
- 📜 Hiring & contracts
- 🎓 Training & certifications
- 💬 Community & networking
- 💰 Financial tracking

---

## 🚀 Getting Started

### Prerequisites

| Tool | Version |
|------|---------|
| PHP | 8.2+ |
| Composer | Latest |
| Node.js | 18+ |
| MariaDB/MySQL | 8.0+ |

### Installation

```bash
# 1. Clone project
git clone https://github.com/yosrthabet/Esprit-PIDEV-3A8-2526-Skilora_TN.git
cd Esprit-PIDEV-3A8-2526-Skilora_TN

# 2. Install dependencies
composer install
npm install

# 3. Configure environment
cp .env .env.local
# Edit .env.local with your DB credentials

# 4. Create database & run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Build frontend assets
npm run build

# 6. Run server
symfony server:start
```

---

## ✨ Features

### 👤 For Candidates

- 🔐 Secure authentication (Symfony Security)
- 📄 Smart profile (skills, CV, experience)
- 🔍 Job feed with filtering
- 📬 Apply to jobs easily
- 🎓 Online training & quizzes
- 💬 Messaging system
- 💰 Salary & contract tracking

### 🏢 For Employers

- 📋 Create job offers
- 📥 Manage applications
- 📅 Schedule interviews
- 📜 Generate contracts
- 📊 Dashboard analytics

### 🛡️ Admin Panel

- 👥 User management
- 🎓 Course management
- 🎫 Support tickets
- 🚩 Reports & moderation

---

## 🤖 AI Integration

Skilora integrates AI-powered features directly from PHP:

- 🤖 AI chatbot for training & course recommendations
- 📊 Candidate-job matching score engine
- 🧠 ML-based anomaly detection & spending analysis

---

## 🌍 Multilingual Support

| Language | Code | Status |
|----------|------|--------|
| 🇫🇷 Français | `fr` | ✅ Default |
| 🇬🇧 English | `en` | ✅ Full |
| 🇸🇦 العربية | `ar` | ✅ Full RTL |

---

## 🏗️ Project Architecture

```
Skilora (Symfony 6.4)
├── src/
│   ├── Controller/          # Route handlers (Auth, Admin, Community, Finance, etc.)
│   ├── Entity/              # Doctrine ORM entities
│   ├── Repository/          # Database queries
│   ├── Service/             # Business logic (Chatbot, Finance, PDF, etc.)
│   ├── Form/                # Symfony form types
│   ├── Recruitment/         # Recruitment module (entities, controllers, services)
│   ├── Formation/           # Formation module (courses, certificates)
│   ├── Finance/             # Finance module (wallet, escrow, contracts)
│   ├── Support/             # Support module (tickets, FAQ)
│   ├── Security/            # Authentication & authorization
│   └── Validator/           # Custom validation constraints
├── templates/               # Twig templates
│   ├── community/
│   ├── recruitment/
│   ├── formation/
│   ├── finance/
│   ├── support/
│   └── components/          # Reusable UI components
├── assets/                  # Frontend source (Tailwind CSS, Alpine.js)
├── public/                  # Web root
├── config/                  # Symfony configuration
├── translations/            # i18n (EN, FR, AR)
├── migrations/              # Doctrine migrations
└── tests/                   # PHPUnit tests
```

---

## 🎨 Tech Stack

```
Frontend   →  Twig + Tailwind CSS 3 + Alpine.js
Backend    →  Symfony 6.4 + PHP 8.2
Database   →  MySQL 8.0 + Doctrine ORM
AI/ML      →  OpenAI-compatible API + Python ML services
Assets     →  Vite
Testing    →  PHPUnit
Hosting    →  VPS (skilora.dev)
```

---

## 📦 Main Modules

- 💼 **Recruitment** — Jobs, applications, interviews, offers, contracts
- 🎓 **Formation** — Courses, enrollments, quizzes, certificates
- 💬 **Community** — Posts, events, connections, groups, blog
- 💰 **Finance** — Wallet, escrow, payslips, invoices, disputes
- 🎫 **Support** — Tickets, FAQ, AI chatbot, calendar
- ⚙️ **Settings** — Profile, security, language, theme

---

## 👨‍💻 Team

Developed by engineering students at **ESPRIT**, Tunisia 🇹🇳

---

## 📄 License

This project is developed for academic purposes at **ESPRIT — École Supérieure Privée d'Ingénierie et de Technologies**, Tunisia.

---

<div align="center">

Made with ❤️ in Tunisia 🇹🇳

**[⭐ Star this repo](https://github.com/yosrthabet/Esprit-PIDEV-3A8-2526-Skilora_TN)** if Skilora inspired you!

</div>
