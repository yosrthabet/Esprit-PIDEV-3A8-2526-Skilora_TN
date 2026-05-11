# Skilora — Feature-by-Feature Comparison

**REF** = `/Users/nyxfallagatn/ESPRIT/WEB`  
**FINAL** = `/Users/nyxfallagatn/Desktop/WEB_Final`

Legend: ✅ Present | ❌ Missing | ⚠️ Partial | 🔄 Different implementation

---

## 1. Authentication & Security

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Email/password login | ✅ | ✅ | |
| OAuth (Google/GitHub) | ✅ | ✅ | |
| 2FA (TOTP) | ✅ | ✅ | |
| Passkey/WebAuthn | ✅ | ✅ | |
| Forgot/Reset password | ✅ | ✅ | |
| Email verification | ✅ | ✅ | |
| Login history tracking | ✅ | ✅ | |
| Session management | ✅ | ✅ | |
| Role-based access (ADMIN, EMPLOYER, TRAINER, USER) | ✅ | ✅ | |
| Security headers subscriber | ✅ | ✅ | |
| Doctrine session config | ✅ | ✅ | |
| Custom password hasher | ✅ | ✅ | |
| Role-based workspace redirect | ✅ | ✅ | |

---

## 2. User Profile

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Profile CRUD | ✅ | ✅ | |
| Experiences management | ✅ | ✅ | |
| Skills management | ✅ | ✅ | |
| Portfolio items | ✅ | ✅ | |
| Avatar upload | ✅ | ✅ | |
| Profile completion % | ✅ | ✅ | |
| AI bio generation | ✅ | ✅ | |
| Public profile page | ✅ | ✅ | |
| Security settings (sessions, login history) | ✅ | ✅ | |
| Cloudinary upload | ✅ | ⚠️ | FINAL has CloudinaryService stub, wired but needs real credentials |
| Dashboard data provider | ✅ | ✅ | RecruitmentDashboardService provides role-specific KPIs |

---

## 3. Recruitment — Job Offers

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Browse job listings | ✅ | ✅ | |
| Job search/filter | ✅ | ✅ | FINAL has `app_job_search_api` JSON endpoint |
| Job detail page | ✅ | ✅ | |
| Apply to job | ✅ | ✅ | |
| Save/unsave jobs | ✅ | ✅ | |
| Job preferences | ✅ | ✅ | |
| Employer post job | ✅ | ✅ | |
| Employer edit job | ✅ | ✅ | |
| Employer close job | ✅ | ✅ | |
| Employer delete job | ✅ | ✅ | |
| Employer reopen job | ❌ | ✅ | **FINAL only** |
| Employer duplicate job | ❌ | ✅ | **FINAL only** |
| Job offer pagination (server-side) | ✅ | ❌ | REF has dedicated `/page` endpoint |
| Company public profile | ✅ | ✅ | CompanyPublicController + template |
| ANETI external job feed import | ✅ | ❌ | |
| Job feed import command | ✅ | ✅ | |

---

## 4. Recruitment — Applications

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| View my applications (candidate) | ✅ | ✅ | |
| Delete application | ✅ | ✅ | Implemented in RecruitmentController |
| View applications (employer) | ✅ | ✅ | |
| View candidate profile (employer) | ✅ | ✅ | |
| View cover letter (employer) | ✅ | ✅ | |
| View/download CV (employer) | ✅ | ✅ | |
| Change application status | ✅ | ✅ | |
| Application quality screening | ✅ | ❌ | REF has AI-powered quality scoring |
| Application pipeline service | ❌ | ✅ | **FINAL has dedicated pipeline service** |
| CV text extraction (OCR) | ✅ | ❌ | |
| CV-job matching score | ✅ | ⚠️ | FINAL has `JobMatchService` (simpler) |

---

## 5. Recruitment — Interviews

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Schedule interview (employer) | ✅ | ✅ | |
| View interviews (candidate) | ✅ | ✅ | |
| View interviews (employer) | ✅ | ✅ | |
| Interview countdown script | ✅ | ✅ | `_interview_countdown.html.twig` Alpine.js component |
| WhatsApp interview notification | ✅ | ⚠️ | TwilioService stub created, needs real credentials |
| Meeting link generation | ✅ | ✅ | Both have MeetingLinkFactory |
| Interview form type | ✅ | ❌ | FINAL uses inline form handling |

---

## 6. Recruitment — Hire Offers

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Create hire offer (employer) | ✅ | ✅ | |
| View hire offers (candidate) | ✅ | ✅ | |
| Accept hire offer | ✅ | ✅ | |
| Reject hire offer | ✅ | ✅ | |
| View hire offers (employer) | ✅ | ✅ | |

---

## 7. Recruitment — CV Builder

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| CV builder form UI | ✅ | ✅ | |
| CV builder data model | ✅ | ✅ | |
| Classic PDF template | ✅ | ✅ | |
| Modern PDF template | ✅ | ✅ | |
| CV PDF generation service | ✅ | ✅ | |
| CV form types (Education, Experience) | ✅ | ❌ | REF has dedicated form types |

---

## 8. Recruitment — AI/ML

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Skill matching API | ✅ | ✅ | RecruitmentMlController + SkiloraMlClient |
| Semantic matching API | ✅ | ✅ | RecruitmentMlController |
| CV analysis API | ✅ | ✅ | RecruitmentMlController |
| Salary prediction API | ✅ | ✅ | RecruitmentMlController |
| Interview questions generation | ✅ | ✅ | RecruitmentMlController |
| ML dashboard (5-tab UI) | ✅ | ✅ | `recruitment/ml/index.html.twig` |

---

## 9. Formation — Course Catalog

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Browse formations | ✅ | ✅ | |
| Formation detail page | ✅ | ✅ | |
| Enroll in formation | ✅ | ✅ | |
| Formation reviews/ratings | ✅ | ✅ | |
| Review voting (like/dislike) | ✅ | ✅ | ReviewVote entity + vote route + UI |
| Formation search/filter toolbar | ✅ | ❌ | REF has `_catalog_toolbar.html.twig` |
| Formation card partial | ✅ | ❌ | REF has `_card.html.twig` |

---

## 10. Formation — Learning

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| My formations list | ✅ | ✅ | |
| Learning progress tracking | ✅ | ✅ | |
| Module toggle completion | ✅ | ✅ | |
| Formation completion | ✅ | ✅ | |
| My certificates list | ✅ | ✅ | |
| Certificate preview | ✅ | ✅ | |
| Certificate PDF download | ✅ | ✅ | |
| Certificate QR code | ✅ | ✅ | |
| Certificate verification (public) | ✅ | ✅ | |
| Certificate show page | ❌ | ✅ | **FINAL only** |
| Learning show page (in-progress course) | ❌ | ✅ | **FINAL only** |
| Certificate PDF generator service | ✅ | ❌ | REF has dedicated service |
| Certificate QR code service | ✅ | ❌ | REF has dedicated service |

---

## 11. Formation — Trainer

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Trainer formations list | ✅ | ✅ | |
| Create formation | ✅ | ✅ | |
| Edit formation | ✅ | ✅ | |
| Delete formation | ✅ | ✅ | |
| Manage modules | ✅ | ✅ | |
| Manage materials | ✅ | ✅ | |
| Trainer students view | ❌ | ✅ | **FINAL only** — see enrolled students |
| Form types | ✅ | ❌ | REF has FormationType, FormationFormConfigurator |

---

## 12. Formation — Admin

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Admin formation list | ✅ | ✅ | |
| Admin create formation | ✅ | ✅ | |
| Admin edit formation | ✅ | ✅ | |
| Admin delete formation | ✅ | ✅ | |
| Admin publish formation | ❌ | ✅ | **FINAL only** |
| Admin archive formation | ❌ | ✅ | **FINAL only** |
| Admin formation show | ❌ | ✅ | **FINAL only** |
| Signature preview | ✅ | ✅ | |
| Admin certificates list | ✅ | ✅ | `app_admin_certificates` route + template |

---

## 13. Formation — AI/ML

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Course recommendations API | ✅ | ✅ | FormationMlController + SkiloraMlClient |
| Completion prediction API | ✅ | ✅ | FormationMlController |
| ML dashboard (2-tab UI) | ✅ | ✅ | `formation/ml/index.html.twig` |
| Formation chatbot | ✅ | ❌ | REF has ChatbotController + service |
| Chatbot floating widget | ✅ | ❌ | |

---

## 14. Formation — Other

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Quiz system | ✅ | ✅ | Quiz, QuizQuestion, QuizResult entities + admin CRUD + student UI |
| Feedback entity | ✅ | ❌ | Post-formation feedback |
| Formation duration validator | ✅ | ✅ | FormationDurationConsistent validator |
| Unique title validator | ✅ | ✅ | UniqueFormationTitle validator |
| Enrollment status enum | ❌ | ✅ | **FINAL only** |
| FormationNotifier | ❌ | ✅ | **FINAL only** |

---

## 15. Finance — Contracts

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Employer contract list | ✅ | ✅ | |
| Employer contract detail | ✅ | ✅ | |
| Freelancer contract list | ✅ | ✅ | |
| Freelancer contract detail | ✅ | ✅ | |
| Approve delivery | ✅ | ✅ | |
| Submit delivery | ✅ | ✅ | |
| Start work | ✅ | ✅ | |
| Raise dispute | ✅ | ✅ | |
| Terminate contract | ✅ | ✅ | |
| Fund escrow | ✅ | ✅ | |
| Release payment | ❌ | ✅ | **FINAL only** |
| Contract lifecycle service | ✅ | 🔄 | REF: ContractLifecycleService, FINAL: ContractService |
| ContractDelivery entity | ❌ | ✅ | **FINAL only** — delivery tracking |
| ContractDispute entity | ❌ | ✅ | **FINAL only** — disputes as entity |

---

## 16. Finance — Wallet & Payments

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Wallet entity | ✅ | ✅ | Wallet.php with credit/debit |
| Wallet balance view | ✅ | ✅ | In finance dashboard |
| Wallet recharge | ✅ | ✅ | StripeController checkout |
| Stripe checkout session | ✅ | ✅ | StripeController + StripeService |
| Stripe webhook handler | ✅ | ✅ | `/webhook/stripe` route |
| Wallet service | ✅ | ✅ | WalletService |

---

## 17. Finance — Invoices

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Employer invoice list | ✅ | ✅ | |
| Freelancer invoice list | ✅ | ✅ | |
| Invoice detail | ✅ | ✅ | |
| Auto-generate on contract completion | ✅ | ✅ | ContractLifecycleService triggers InvoiceService |
| Invoice service | ✅ | ✅ | InvoiceService |

---

## 18. Finance — Escrow

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Escrow overview | ✅ | ✅ | |
| Fund escrow | ✅ | ✅ | |
| EscrowAccount entity | ✅ | ❌ | REF uses account-based |
| EscrowTransaction entity | ❌ | ✅ | FINAL uses transaction-based |
| EscrowService | ✅ | ✅ | EscrowService |

---

## 19. Finance — Advanced (REF Only)

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Payslip generation | ✅ | ✅ | Payslip entity + admin CRUD |
| Payslip PDF export | ✅ | ❌ | |
| Payslip pay & notify | ✅ | ⚠️ | Entity exists, notification stub |
| Bank account CRUD | ✅ | ✅ | BankAccount entity |
| Bonus/Milestone payments | ✅ | ✅ | ContractMilestone entity + MilestoneController |
| Exchange rate entity | ✅ | ✅ | ExchangeRate entity + admin CRUD |
| Payment transactions log | ✅ | ✅ | PaymentTransaction entity |
| Job reviews/ratings | ✅ | ✅ | JobReview entity + review route |
| Finance analytics service | ✅ | ❌ | |
| Finance forecast (AI) | ✅ | ❌ | AI-powered financial forecasting |
| Finance CSV export | ✅ | ✅ | FinanceExportService + 3 CSV export routes |
| Finance Excel export | ✅ | ❌ | |
| WhatsApp payment notification | ✅ | ⚠️ | TwilioService stub (needs real creds) |
| SMS payment notification | ✅ | ⚠️ | TwilioService stub |
| Employer finance dashboard | ✅ | ✅ | Role-conditional in freelancer.html.twig |
| Freelancer finance dashboard | ✅ | ✅ | FinanceDashboardController |
| Finance ML: anomaly detection | ✅ | ✅ | FinanceMlController |
| Finance ML: spending analysis | ✅ | ✅ | FinanceMlController |
| Admin finance: 10 controllers | ✅ | ❌ | |
| Dispute resolution (admin) | ✅ | ⚠️ | FINAL has basic resolve, REF is fuller |

### FINAL Only
| Feature | FINAL |
|---------|-------|
| ContractDelivery entity (delivery tracking) | ✅ |
| ContractDispute entity (separate disputes) | ✅ |
| EscrowTransaction entity (transaction-based) | ✅ |
| FinanceNotifier service | ✅ |
| Unified finance dashboard | ✅ |
| Contract release payment | ✅ |
| Admin finance overview | ✅ |

---

## 20. Community

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Posts feed | ✅ | ✅ | |
| Create post | ✅ | ✅ | |
| Delete post | ✅ | ✅ | |
| Like/unlike post | ✅ | ✅ | |
| Comment on post | ✅ | ✅ | |
| Edit post | ✅ | ✅ | CommunityController edit route |
| Blog listing | ✅ | ✅ | |
| Blog article create | ❌ | ✅ | **FINAL only** |
| Blog article show (by slug) | ❌ | ✅ | **FINAL only** |
| Groups listing | ✅ | ✅ | |
| Create group | ✅ | ✅ | |
| Group detail | ✅ | ✅ | |
| Join group | ✅ | ✅ | |
| Leave group | ✅ | ✅ | CommunitySpacesController leave route |
| Edit group | ✅ | ✅ | CommunitySpacesController edit route |
| Delete group | ✅ | ✅ | CommunitySpacesController delete route |
| Events listing | ❌ | ✅ | **FINAL only** |
| Event create | ❌ | ✅ | **FINAL only** |
| Event detail | ❌ | ✅ | **FINAL only** |
| Event RSVP | ❌ | ✅ | **FINAL only** |
| Feed show page (individual post) | ❌ | ✅ | **FINAL only** |
| Admin post moderation (approve/reject) | ❌ | ✅ | **FINAL only** |
| Post translation (AI) | ✅ | ❌ | |
| Community search | ✅ | ❌ | |
| Mention autocomplete | ✅ | ❌ | |
| Community notifications | ✅ | ✅ | CommunityNotifier (like, comment, group join, mention) |
| Content moderation (AI) | ✅ | ❌ | |
| AI summary service | ✅ | ❌ | |
| Community ML endpoints | ✅ | ✅ | CommunityMlController (moderate, sentiment) |
| Network invitations | ✅ | ✅ | |
| Accept/decline/cancel invitation | ✅ | ✅ | |
| DM (in network) | ✅ | 🔄 | FINAL has dedicated messaging module |

---

## 21. Messaging

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Start conversation | ✅ | ✅ | |
| Send message | ✅ | ✅ | |
| Edit message | ✅ | ✅ | MessagingController edit route |
| Delete message | ✅ | ✅ | MessagingController delete route |
| View inbox | ✅ | ✅ | |
| Unread count API | ✅ | ✅ | |
| DmParticipant entity | ❌ | ✅ | **FINAL only** — proper M2M |
| MessagingNotifier | ❌ | ✅ | **FINAL only** |
| Dedicated messaging module | ❌ | ✅ | **FINAL only** — separate namespace |
| Messages API endpoint | ❌ | ✅ | **FINAL only** — JSON API |

---

## 22. Support

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| Create ticket | ✅ | ✅ | |
| View ticket list | ✅ | ✅ | |
| View ticket detail | ✅ | ✅ | |
| Post message on ticket | ✅ | ✅ | |
| Edit ticket | ✅ | ✅ | SupportController edit route |
| Edit message | ✅ | ✅ | SupportController message edit route |
| Delete message | ❌ | ❌ | |
| Close ticket | ✅ | ✅ | SupportController close route |
| PDF export | ✅ | ✅ | |
| Feedback/rating | ✅ | ✅ | Feedback fields on SupportTicket |
| File attachments | ✅ | ✅ | TicketAttachment entity + upload/download |
| Admin ticket list | ✅ | ✅ | |
| Admin ticket show | ✅ | ✅ | |
| Admin status update | ✅ | ✅ | |
| Admin CSV export | ❌ | ✅ | **FINAL only** |
| Admin calendar view | ✅ | ❌ | |
| Admin avis/reviews | ✅ | ❌ | |
| AI subject suggestion | ✅ | ❌ | |
| AI text correction | ✅ | ❌ | |
| AI triage | ✅ | ✅ | SupportMlController triage route |
| AI smart reply | ✅ | ✅ | SupportMlController smart-reply route |
| Chatbot | ✅ | ❌ | |
| Status change email | ✅ | ❌ | |
| SupportNotifier | ❌ | ✅ | **FINAL only** |

---

## 23. Admin Panel

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| User CRUD (list, create, edit, delete) | ✅ | ✅ | Admin user index + edit templates |
| User table partial | ✅ | ✅ | _table.html.twig |
| Recruitment admin (offers list, show) | ✅ | ✅ | Admin recruitment index + show |
| Community moderation (posts table, delete) | ✅ | ⚠️ | FINAL has approve/reject inline |
| Formation admin | ✅ | ✅ | FINAL has more features (publish, archive) |
| Finance admin (10 controllers) | ✅ | ⚠️ | FINAL has basic admin finance |
| Support admin | ✅ | ✅ | |
| Certificates admin | ✅ | ✅ | `app_admin_certificates` route + template |

---

## 24. Infrastructure / Cross-cutting

| Feature | REF | FINAL | Notes |
|---------|-----|-------|-------|
| PWA manifest | ✅ | ✅ | |
| Service worker | ✅ | ✅ | |
| Offline page | ✅ | ✅ | |
| Hand gesture detection | ✅ | ✅ | |
| Vite for asset bundling | ✅ | ✅ | |
| Docker compose | ✅ | ✅ | |
| PHPStan config | ✅ | ✅ | |
| Deploy script | ✅ | ✅ | |
| Mercure (real-time) | ✅ | ⚠️ | MercureService stub created, needs hub setup |
| Calendar routes config | ✅ | ❌ | |
| Cloudinary integration | ✅ | ⚠️ | CloudinaryService stub created |
| i18n translations | ✅ | ✅ | |
| Database seed command | ❌ | ✅ | **FINAL only** |
| Database audit command | ❌ | ✅ | **FINAL only** |
| Database session fix command | ❌ | ✅ | **FINAL only** |
| ProdDebugSubscriber | ❌ | ✅ | **FINAL only** |
