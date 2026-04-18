<div align="center">

<img src="public/assets/logo.png" alt="Skilora Logo" width="120" height="120"/>

# 🌟 Skilora TN

### *Tunisia's All-in-One Talent & Career Ecosystem*

[![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge\&logo=php\&logoColor=white)](https://www.php.net/)
[![Symfony](https://img.shields.io/badge/Symfony-6.4-000000?style=for-the-badge\&logo=symfony\&logoColor=white)](https://symfony.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge\&logo=mysql\&logoColor=white)](https://www.mysql.com/)
[![Twig](https://img.shields.io/badge/Twig-Template-009688?style=for-the-badge)](https://twig.symfony.com/)
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
| MySQL               | 8.0+    |
| Node.js (optional)  | 18+     |
| Python (AI service) | 3.9+    |

---

### ⚙️ Installation

```bash
# 1. Clone project
git clone https://github.com/your-repo/skilora.git
cd skilora

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env .env.local

# Edit DB config
DATABASE_URL="mysql://root:password@127.0.0.1:3306/skilora"

# 4. Create database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 5. Run server
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
Skilora (Symfony)
├── src/
│   ├── Controller/
│   ├── Entity/
│   ├── Repository/
│   ├── Service/
│   ├── Form/
│   └── Security/
│
├── templates/
│   ├── base.html.twig
│   ├── recruitment/
│   ├── formation/
│   └── finance/
│
├── public/
│   ├── assets/
│   └── index.php
│
├── config/
├── migrations/
└── .env
```

---

## 🧱 Tech Stack

| Layer    | Technology       |
| -------- | ---------------- |
| Backend  | Symfony 6.4      |
| Language | PHP 8.2          |
| Database | MySQL 8          |
| ORM      | Doctrine         |
| Frontend | Twig + Bootstrap |
| Auth     | Symfony Security |
| AI       | Python (FastAPI) |
| PDF      | Dompdf           |

---

## 🤖 AI Integration

Skilora integrates AI services using Python APIs:

* Face recognition login
* Smart job matching
* AI chatbot

Example:

```php
$response = $client->request('POST', 'http://localhost:8000/api/ai', [
    'json' => ['data' => $input]
]);
```

---

## 🌍 Multilingual Support

| Language | Code |
| -------- | ---- |
| Français | fr   |
| English  | en   |
| العربية  | ar   |

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
