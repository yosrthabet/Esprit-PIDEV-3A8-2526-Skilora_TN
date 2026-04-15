# Finance Module Architecture

Ce fichier centralise toute la partie Finance pour faciliter le travail en equipe.

## 1) Controllers (entrees HTTP)

- `src/Controller/Admin/Finance/FinanceIndexController.php`
- `src/Controller/Admin/Finance/FinanceReportsController.php`
- `src/Controller/Admin/Finance/FinanceForecastController.php`
- `src/Controller/Admin/Finance/PayslipController.php`
- `src/Controller/Admin/Finance/ContractController.php`
- `src/Controller/Admin/Finance/BonusController.php`
- `src/Controller/Admin/Finance/BankAccountController.php`
- `src/Controller/Admin/Finance/ProjectPaymentController.php`

## 2) Services metier Finance

### Services deja regroupes dans `src/Service/Finance/`

- `src/Service/Finance/FinanceForecastService.php`
- `src/Service/Finance/FinanceForecastAiCommentService.php`
- `src/Service/Finance/FinanceForecastChartFactory.php`
- `src/Service/Finance/FinanceForecastExcelExportService.php`
- `src/Service/Finance/FinancePdfAiSummaryService.php`
- `src/Service/Finance/FinanceStripeClient.php`
- `src/Service/Finance/PayslipPaymentSmsService.php`
- `src/Service/Finance/PayslipPaymentSmsResult.php`
- `src/Service/Finance/PayAndNotifyResult.php`
- `src/Service/Finance/PaymentSuccessWhatsAppMessageFactory.php`
- `src/Service/Finance/StripeMoney.php`
- `src/Service/Finance/TwilioWhatsAppNotifier.php`

### Services Finance encore en racine `src/Service/`

- `src/Service/FinanceAnalyticsService.php`
- `src/Service/FinancePdfExportService.php`
- `src/Service/PayslipPayrollCalculator.php`

Note: ces 3 services sont Finance mais conserves a cet emplacement pour eviter les regressions de namespace lors de l'integration avec les autres branches.

## 3) Entites Finance

- `src/Entity/Finance/Contract.php`
- `src/Entity/Finance/Payslip.php`
- `src/Entity/Finance/Bonus.php`
- `src/Entity/Finance/BankAccount.php`
- `src/Entity/Finance/Company.php`
- `src/Entity/Finance/ExchangeRate.php`

## 4) Repositories Finance

- `src/Repository/Finance/ContractRepository.php`
- `src/Repository/Finance/PayslipRepository.php`
- `src/Repository/Finance/BonusRepository.php`
- `src/Repository/Finance/BankAccountRepository.php`
- `src/Repository/Finance/CompanyRepository.php`
- `src/Repository/Finance/ExchangeRateRepository.php`

## 5) Forms Finance

- `src/Form/Finance/ContractType.php`
- `src/Form/Finance/PayslipType.php`
- `src/Form/Finance/BonusType.php`
- `src/Form/Finance/BankAccountType.php`

## 6) Validation Finance

- `src/Validation/Finance/FinanceAllowedValues.php`

## 7) Templates Finance

### Admin

- `templates/admin/finance/layout.html.twig`
- `templates/admin/finance/_finance_tabs.html.twig`
- `templates/admin/finance/reports/index.html.twig`
- `templates/admin/finance/forecast/index.html.twig`
- `templates/admin/finance/payslip/*.twig`
- `templates/admin/finance/contract/*.twig`
- `templates/admin/finance/bonus/*.twig`
- `templates/admin/finance/bank_account/*.twig`
- `templates/admin/finance/project_payment/index.html.twig`

### PDF

- `templates/finance/report/employee_report.html.twig`
- `templates/finance/report/payslip_slip.html.twig`

## 8) Config Finance

- `config/services.yaml` (injections des services Finance)
- `config/packages/dh_auditor.yaml` (audit Finance)
- `config/routes/dh_auditor.yaml` (routes viewer audit)

## 9) Routes importantes

- `/admin/finance`
- `/admin/finance/reports`
- `/admin/finance/reports/export/employee/{id}.pdf`
- `/admin/finance/forecast`
- `/admin/finance/forecast/export.xlsx`
- `/admin/finance/payslips`
- `/admin/finance/contracts`
- `/admin/finance/bonuses`
- `/admin/finance/bank-accounts`
- `/admin/finance/project-payment`
- `/audit` (viewer audit)

## 10) Convention d'equipe proposee

- Toute nouvelle logique Finance => `src/Service/Finance/`
- Toute nouvelle page admin Finance => `templates/admin/finance/...`
- Toute nouvelle API Finance => `src/Controller/Admin/Finance/...`
- Commits avec prefixe conseille: `finance: ...`

