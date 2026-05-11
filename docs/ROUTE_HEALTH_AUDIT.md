# Route Health Audit — 2026-05-09

**Method:** Automated curl testing via `/dev/login/{role}` for all 4 roles.
**Database:** Validated with `doctrine:schema:validate` — fully in sync.

## Issues Found & Fixed

| # | Route | Error | Root Cause | Fix |
|---|-------|-------|------------|-----|
| 1 | `/finance`, `/finance/wallet`, `/finance/bank-accounts`, `/finance/escrow`, `/finance/invoices`, `/admin/finance/exchange-rates`, `/admin/finance/payslips` | 500 — `Table 'skilorafinal.finance_wallets' does not exist` | 6 Finance entities used `finance_*` table names but no migration created them | `migrations/finance_missing_tables.sql` + `doctrine:schema:update --force` |
| 2 | `/admin/users` | 500 — `Property "createdAt" has no public accessor on User` | `User::getCreatedAt()` getter was missing | Added getter to `src/Entity/User.php` |
| 3 | `skilora_app` DB user | 500 — `Access denied for user 'skilora_app'@'localhost'` | GRANT command created duplicate user entry without password | Reset password + flush privileges |

## Tables Created

| Table | Entity | Status |
|-------|--------|--------|
| `finance_wallets` | `Wallet` | ✅ Created |
| `finance_bank_accounts` | `BankAccount` | ✅ Created |
| `finance_payslips` | `Payslip` | ✅ Created |
| `finance_payment_transactions` | `PaymentTransaction` | ✅ Created |
| `finance_exchange_rates` | `ExchangeRate` | ✅ Created |
| `finance_contract_milestones` | `ContractMilestone` | ✅ Created |
| `formation_feedback` | `FormationFeedback` | ✅ Created (bonus) |
| `formation_quizzes` (new) | `FormationQuiz` | ✅ Created (bonus) |
| `formation_quiz_questions` | `FormationQuizQuestion` | ✅ Created (bonus) |
| `formation_quiz_results` | `FormationQuizResult` | ✅ Created (bonus) |

## Full Route Results

### ADMIN (26 routes)
| Status | Route |
|--------|-------|
| ✅ 200 | `/admin/finance` |
| ✅ 200 | `/admin/finance/exchange-rates` |
| ✅ 200 | `/admin/finance/payslips` |
| ✅ 200 | `/admin/finance/reviews` |
| ✅ 200 | `/admin/support` |
| ✅ 200 | `/admin/support/calendar` |
| ✅ 200 | `/admin/community` |
| ✅ 200 | `/admin/users` |
| ✅ 200 | `/admin/formations` |
| ✅ 200 | `/admin/certificates` |
| ✅ 200 | `/finance` |
| ✅ 200 | `/finance/wallet` |
| ✅ 200 | `/finance/bank-accounts` |
| ✅ 200 | `/finance/escrow` |
| ✅ 200 | `/finance/invoices` |
| ✅ 200 | `/finance/analytics` |
| ✅ 200 | `/contracts` |
| ✅ 200 | `/notifications` |
| ✅ 200 | `/settings` |
| ✅ 200 | `/chatbot` |
| ✅ 200 | `/community` |
| 🔒 403 | `/community/network` (admin role excluded — by design) |
| ✅ 200 | `/community/groups` |
| ✅ 200 | `/community/events` |
| ✅ 200 | `/community/blog` |
| ✅ 200 | `/inbox` |

### USER / FREELANCER (25 routes)
| Status | Route |
|--------|-------|
| ✅ 200 | `/finance` |
| ✅ 200 | `/finance/wallet` |
| ✅ 200 | `/finance/bank-accounts` |
| ✅ 200 | `/finance/escrow` |
| ✅ 200 | `/finance/invoices` |
| ✅ 200 | `/finance/analytics` |
| ✅ 200 | `/contracts` |
| ✅ 200 | `/jobs` |
| ✅ 200 | `/applications` |
| ✅ 200 | `/hire-offers` |
| ✅ 200 | `/job-preferences` |
| ✅ 200 | `/formations` |
| ✅ 200 | `/learning` |
| ✅ 200 | `/certificates` |
| ✅ 200 | `/community` |
| ✅ 200 | `/community/network` |
| ✅ 200 | `/community/groups` |
| ✅ 200 | `/community/events` |
| ✅ 200 | `/community/blog` |
| ✅ 200 | `/support` |
| ✅ 200 | `/inbox` |
| ✅ 200 | `/notifications` |
| ✅ 200 | `/settings` |
| ✅ 200 | `/chatbot` |
| ↩️ 302 | `/recruitment` (redirects to `/jobs` — by design) |

### EMPLOYER (25 routes)
| Status | Route |
|--------|-------|
| ✅ 200 | `/finance` |
| ✅ 200 | `/finance/wallet` |
| ✅ 200 | `/finance/bank-accounts` |
| ✅ 200 | `/finance/escrow` |
| ✅ 200 | `/finance/invoices` |
| ✅ 200 | `/finance/analytics` |
| ✅ 200 | `/contracts` |
| ✅ 200 | `/jobs` |
| ✅ 200 | `/applications` |
| ✅ 200 | `/hire-offers` |
| ✅ 200 | `/post-job` |
| ✅ 200 | `/active-offers` |
| ✅ 200 | `/interviews` |
| ✅ 200 | `/community` |
| ✅ 200 | `/community/network` |
| ✅ 200 | `/community/groups` |
| ✅ 200 | `/community/events` |
| ✅ 200 | `/community/blog` |
| ✅ 200 | `/support` |
| ✅ 200 | `/inbox` |
| ✅ 200 | `/notifications` |
| ✅ 200 | `/settings` |
| ✅ 200 | `/chatbot` |
| ✅ 200 | `/recruitment/ml` |
| ↩️ 302 | `/recruitment` (redirects — by design) |

### TRAINER (21 routes)
| Status | Route |
|--------|-------|
| ✅ 200 | `/finance` |
| ✅ 200 | `/finance/wallet` |
| ✅ 200 | `/finance/bank-accounts` |
| ✅ 200 | `/finance/invoices` |
| ✅ 200 | `/finance/analytics` |
| ✅ 200 | `/contracts` |
| ✅ 200 | `/formations` |
| ✅ 200 | `/learning` |
| ✅ 200 | `/certificates` |
| ✅ 200 | `/trainer/formations` |
| ✅ 200 | `/community` |
| ✅ 200 | `/community/network` |
| ✅ 200 | `/community/groups` |
| ✅ 200 | `/community/events` |
| ✅ 200 | `/community/blog` |
| ✅ 200 | `/support` |
| ✅ 200 | `/inbox` |
| ✅ 200 | `/notifications` |
| ✅ 200 | `/settings` |
| ✅ 200 | `/chatbot` |
| ✅ 200 | `/formation/ml` |

## Summary

- **97 route checks** across 4 roles
- **0 errors remaining** (all 500s fixed)
- **1 intentional 403** (admin excluded from `/community/network`)
- **2 intentional 302** redirects (`/recruitment` → `/jobs`)
- **Doctrine schema:** ✅ Fully in sync
