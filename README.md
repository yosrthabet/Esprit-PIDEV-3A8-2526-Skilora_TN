<div align="center">

<img src="public/assets/logo.png" alt="Skilora Logo" width="120" height="120"/>

# 🌟 Skilora TN

### *Tunisia's All-in-One Talent & Career Ecosystem*

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge\&logo=php\&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?style=for-the-badge\&logo=symfony\&logoColor=white)](https://symfony.com/)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.4-003545?style=for-the-badge\&logo=mariadb\&logoColor=white)](https://mariadb.org/)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?style=for-the-badge\&logo=tailwindcss\&logoColor=white)](https://tailwindcss.com/)
[![License](https://img.shields.io/badge/License-Academic-green?style=for-the-badge)](LICENSE)

---

**Connecting Tunisia's Youth to Global Opportunities.**
*Secure · Intelligent · Limitless*

</div>

---

## 👋 Welcome to Skilora

**Skilora** is a modern **web platform** built with Symfony, designed for the Tunisian job market. It connects talents, companies, and training opportunities into one unified ecosystem.

Whether you're:

* 🎓 A student
* 💼 A job seeker
* 🏢 A company

👉 Skilora gives you everything in one place.

---

## 🎯 What Is Skilora?

Skilora is a **talent recruitment and career development platform** that covers:

* 🔍 Job search & applications
* 📅 Interview management
* 📜 Hiring & contracts
* 🎓 Training & certifications
* 💬 Community & networking
* 💰 Financial tracking

---

## 🚀 Getting Started

### 🔧 Prerequisites

| Tool                | Version |
| ------------------- | ------- |
| PHP                 | 8.2+    |
| Composer            | Latest  |
| Symfony CLI         | Latest  |
| MariaDB / MySQL     | 10.4+   |
| Node.js             | 18+     |

---

### ⚙️ Installation

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

* 🔐 Secure authentication (Symfony Security)
* 📄 Smart profile (skills, CV, experience)
* 🔍 Job feed with filtering
* 📬 Apply to jobs easily
* 🎓 Online training & quizzes
* 💬 Messaging system
* 💰 Salary & contract tracking

---

### 🏢 For Employers

* 📋 Create job offers
* 📥 Manage applications
* 📅 Schedule interviews
* 📜 Generate contracts
* 📊 Dashboard analytics

---

### 🛡️ Admin Panel

* 👥 User management
* 🎓 Course management
* 🎫 Support tickets
* 🚩 Reports & moderation

---

## 🏗️ Project Architecture

```
Skilora (Symfony 6.4)
├── src/
│   ├── Controller/       # Route handlers (Auth, Admin, Community, Finance, etc.)
│   ├── Entity/            # Doctrine ORM entities
│   ├── Repository/        # Database queries
│   ├── Service/           # Business logic (Chatbot, Finance, PDF, etc.)
│   ├── Form/              # Symfony form types
│   ├── Recruitment/       # Recruitment module (entities, controllers, services)
│   ├── Security/          # Authentication & authorization
│   └── Validator/         # Custom validation constraints
│
├── templates/             # Twig templates
│   ├── base.html.twig
│   ├── community/
│   ├── recruitment/
│   ├── formation/
│   ├── finance/
│   ├── support/
│   └── components/        # Reusable UI components
│
├── assets/                # Frontend source (Tailwind CSS, Alpine.js)
├── public/                # Web root
├── config/                # Symfony configuration
├── migrations/            # Doctrine migrations
└── tests/                 # PHPUnit tests
```

---

## 🧱 Tech Stack

| Layer    | Technology              |
| -------- | ----------------------- |
| Backend  | Symfony 6.4             |
| Language | PHP 8.2                 |
| Database | MariaDB 10.4            |
| ORM      | Doctrine                |
| Frontend | Twig + Tailwind CSS 4   |
| JS       | Alpine.js               |
| Auth     | Symfony Security        |
| Realtime | Mercure                 |
| PDF      | Dompdf                  |
| Payments | Stripe                  |

---

## 🤖 AI Integration

Skilora integrates an AI-powered chatbot for formation guidance, using an OpenAI-compatible API directly from PHP.

* 🤖 AI chatbot for training & course recommendations
* 📊 Candidate-job matching score engine

---

## 🌍 Language

The platform interface is currently in **English**, with the framework ready for multilingual support via Symfony's translation component.

---

## 🔄 Migration from JavaFX

| Java Desktop | Symfony Web         |
| ------------ | ------------------- |
| JavaFX UI    | Twig                |
| JDBC         | Doctrine            |
| Controllers  | Symfony Controllers |
| Desktop App  | Web App             |

---

## 📦 Main Modules

* 💼 Recruitment
* 🎓 Formation
* 💬 Community
* 💰 Finance
* 🎫 Support
* ⚙️ Settings

---

## 👨‍💻 Team

Developed by engineering students at ESPRIT, Tunisia 🇹🇳

---

## 📄 License

Academic project.

---

<div align="center">

Made with ❤️ in Tunisia 🇹🇳

</div>
