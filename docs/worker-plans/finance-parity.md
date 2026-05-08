# Finance Parity Worker Plan

Branch: `feat/finance-parity`  
Worktree: `/Users/nyxfallagatn/Desktop/WEB_Worktrees/finance-parity`  
Coordinator branch: `coord/platform-checkpoint`

## Mission

Restore old Finance UX and workflow parity in controlled slices while preserving current clean `App\Finance` contract/escrow/dispute/invoice model.

Current baseline already includes:

- Contracts from accepted hire offers.
- Escrow funding state.
- Delivery submission.
- Employer approval.
- Escrow release.
- Disputes.
- Invoices.
- Basic admin finance page.

## File Ownership

Allowed to edit:

- `src/Finance/**`
- `templates/finance/**`
- `tests/**/*Finance*.php`
- `tests/Controller/FinanceRouteRegistrationTest.php`

Do not edit without coordinator approval:

- `config/routes.yaml`
- `config/packages/doctrine.yaml`
- `migrations/*`
- `src/Twig/AppExtension.php`
- `composer.json`
- `composer.lock`
- global dashboards
- unrelated modules

## Old Reference Files To Use

Inspect and adapt from:

- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Finance/WalletController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Finance/StripeController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Finance/InvoiceController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Finance/EscrowController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Admin/Finance/*.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/finance/**`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/admin/finance/**`

## Approved Slice A: Finance Dashboard, No Migration

Goal: restore role-aware finance overview using current schema.

Implement:

- `GET /finance` as dashboard, not just contract list, if feasible without breaking `/contracts`.
- Keep `/contracts` as contract list.
- Dashboard sections:
  - contract counts by status
  - active escrow amount total
  - paid invoice total
  - open disputes
  - recent transactions

Suggested files:

- new `src/Finance/Controller/FinanceDashboardController.php` or extend existing controller carefully
- `templates/finance/dashboard/index.html.twig`

Important:

- If changing `/finance` route from existing `ContractController::index`, ensure route conflict is resolved in module code. Do not edit route config.

## Approved Slice B: Invoice List/Detail, No Migration

Goal: expose current invoice entity to users.

Implement repository methods:

- `InvoiceRepository::findForUser(User $user)`
- `InvoiceRepository::isVisibleToUser(Invoice $invoice, User $user)` or service check

Routes:

- `GET /finance/invoices`
- Route name: `app_finance_invoices`
- `GET /finance/invoices/{id}`
- Route name: `app_finance_invoice_show`

Optional old aliases:

- `/mon-espace/factures`
- `/employer/factures`
- `/facture/{id}`

Templates:

- `templates/finance/invoices/index.html.twig`
- `templates/finance/invoices/show.html.twig`

Access:

- invoice issuer, recipient, contract participant, or admin.

## Approved Slice C: Escrow Ledger List, No Migration

Goal: expose current escrow transaction ledger.

Implement repository methods:

- `EscrowTransactionRepository::findForUser(User $user)`

Routes:

- `GET /finance/escrow`
- Route name: `app_finance_escrow`

Optional old alias:

- `/workspace/escrow`

Template:

- `templates/finance/escrow/index.html.twig`

Important:

- Label this clearly as internal escrow ledger, not real payment gateway movement.

## Approved Slice D: Admin Finance Enhancement, No Migration

Goal: make admin finance useful with current schema.

Enhance `/admin/finance` with:

- contract status summary
- total escrow funded/released ledger counts
- invoices issued/paid counts
- open disputes list
- CSV export for contracts/invoices if simple

Suggested routes:

- `GET /admin/finance/contracts.csv`
- `GET /admin/finance/invoices.csv`

## Blocked/Needs Coordinator Approval

These require migrations and product decisions:

- Wallet/connects:
  - proposed `finance_wallets`
  - proposed `finance_wallet_transactions`
- Stripe checkout/webhook:
  - proposed `finance_payment_intents`
  - proposed `finance_stripe_events`
  - webhook signature verification required
- Bank accounts:
  - proposed `finance_bank_accounts`
  - masking/encryption considerations
- Reviews:
  - proposed `finance_reviews`
- Bonuses/milestones:
  - proposed `finance_bonuses` or milestone table
- Payslips:
  - proposed `finance_payslips`
- Forecast/ML persisted reports:
  - needs table/product approval

Do not implement those until coordinator approves schema.

## Tests To Add/Run

Update:

- `tests/Controller/FinanceRouteRegistrationTest.php`
- `tests/Entity/FinanceContractTest.php`

Add if useful:

- invoice visibility tests
- escrow repository/service tests

Run targeted:

```bash
php bin/phpunit tests/Controller/FinanceRouteRegistrationTest.php tests/Entity/FinanceContractTest.php
php bin/console lint:twig templates/finance
vendor/bin/phpstan analyse --no-progress
```

## Worker Report Format

Return:

- Restored features.
- Changed files.
- New routes.
- Needed coordinator migration/nav/config changes.
- Tests run and results.
- Remaining gaps.
