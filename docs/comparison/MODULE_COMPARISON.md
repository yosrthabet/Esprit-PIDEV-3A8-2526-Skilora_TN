# Skilora — Module-by-Module Comparison

**REF** = `/Users/nyxfallagatn/ESPRIT/WEB` (original, feature-complete)  
**FINAL** = `/Users/nyxfallagatn/Desktop/WEB_Final` (current project)

---

## Summary Scoreboard (Final — 2025-05-09)

| Module | REF Files | FINAL Files | REF Routes | FINAL Routes | Coverage |
|--------|-----------|-------------|------------|--------------|----------|
| **User/Auth/Profile** | ~20 | ~20 | ~15 | ~15 | ✅ 95% |
| **Recruitment** | ~55 | ~28 | ~45 | ~42 | ✅ 90% (+CV form types) |
| **Formation** | ~30 | ~40 | ~38 | ~45 | ✅ 95% (quiz, ML, feedback, admin CRUD) |
| **Finance** | ~40 | ~38 | ~35 | ~38 | ✅ 95% (analytics, forecast, reviews, payslip PDF, Stripe) |
| **Community** | ~25 | ~35 | ~30 | ~32 | ✅ 95% (search, mentions, moderation, translation) |
| **Support** | ~15 | ~16 | ~18 | ~20 | ✅ 95% (calendar, status email, ML) |
| **Messaging/DM** | ~8 | ~8 | ~7 | ~8 | ✅ 95% |
| **Admin Panel** | ~25 | ~18 | ~40 | ~25 | ⚠️ 70% (reviews, calendar, payslips, exchange rates) |
| **AI/ML Integration** | ~10 | ~12 | ~16 | ~18 | ✅ 95% (5 ML controllers + chatbot + translate) |
| **Notifications** | ~5 | ~7 | ~4 | ~4 | ✅ 95% |

**Overall: ~95% coverage** — FINAL has near-complete parity with unique advantages (modular namespaces, 14 enums, chatbot widget, analytics dashboard, delivery/dispute entities).

---

## 1. User / Auth / Profile Module

### REF Has
- Login, Register, OAuth (Google/GitHub), 2FA, Forgot/Reset Password
- Passkey authentication
- Profile CRUD (experiences, skills, portfolio, avatar via Cloudinary)
- Profile completion service, AI bio generation
- Security settings (sessions, login history)
- Dashboard data provider per role
- Workspace redirect service (role-based routing)

### FINAL Has ✅
- All of the above (same files, same structure)
- Additional: `NotificationUrlResolver` service (new in FINAL)

### Missing in FINAL (Updated)
- ~~`DashboardDataProvider` service~~ → Now covered by `RecruitmentDashboardService`
- ~~`CloudinaryUploadService`~~ → `CloudinaryService` stub created (needs real credentials)

---

## 2. Recruitment Module

### REF Has
- **10 controllers** with dedicated separation:
  - `JobOfferController` — Employer CRUD for job offers (list, paginate, create, edit, close, delete)
  - `CandidateJobOfferController` — Candidate browsing, search, apply
  - `CandidateAreaController` — Candidate applications list, interviews, delete
  - `CandidateSavedJobsController` — Save/unsave jobs
  - `CandidateJobPreferencesController` — Set job preferences
  - `CandidateHireOfferController` — View/accept/reject hire offers
  - `EmployerApplicationsController` — View applications, profile, status, cover letter, CV
  - `EmployerDashboardController` — Employer KPIs + finance summary + PDF export
  - `EmployerHireOfferController` — Create hire offers from applications
  - `EmployerInterviewsController` — Schedule interviews
  - `CompanyPublicController` — Public company profiles
  - `RecruitmentMlController` — 5 ML endpoints (match, semantic-match, CV analysis, salary predict, interview questions)
- **18 services** including:
  - `AnetiService` — ANETI job feed import
  - `ApplicationQualityScreeningService` — Application quality scoring
  - `CvDocumentTextExtractor` + `OcrApiTextExtractor` — CV text extraction
  - `CvJobMatchScorer` — CV-to-job matching
  - `EmployerApplicationService` — Application management
  - `EmployerInterviewService` — Interview scheduling logic
  - `HireOfferService` — Hire offer lifecycle
  - `InterviewWhatsAppNotifier` — WhatsApp interview notifications
  - `JobOfferManager` — Job offer business logic
  - `MatchingScoreService` — Candidate-job matching algorithm
  - `RecruitmentNotificationService` — Notification dispatch
- **7 Form types**: CvBuilder, CvEducation, CvExperience, InterviewSchedule, JobApplication, JobOffer
- **Twig extension**, ViewModels, SQL helpers, EventSubscriber
- **Admin recruitment controller** with offers table + show

### FINAL Has
- **1 monolithic controller** (`RecruitmentController`) covering most routes but in a single file
- **1 CV builder controller** (`CandidateCvBuilderController`)
- **7 services**: ApplicationPipeline, ApplicationSubmission, CvPdfGenerator, EmployerJobOffer, JobMatch, MeetingLink, RecruitmentDashboard
- All 7 entities (Application, Company, HireOffer, JobInterview, JobOffer, JobPreference, SavedJob)
- ImportJobFeedCommand

### Missing in FINAL ❌
- [ ] **ML/AI recruitment endpoints** (5 routes for skill matching, CV analysis, salary prediction, interview question generation)
- [ ] **Application quality screening service**
- [ ] **CV text extraction** (OCR API integration)
- [ ] **CV-job match scoring algorithm**
- [ ] **WhatsApp interview notifications** (Twilio integration)
- [ ] **Company public profiles** page
- [ ] **Admin recruitment panel** (admin list + show of all offers)
- [ ] **Employer dashboard** with KPIs and PDF export
- [ ] **Separate form types** (forms are inline in controller)
- [ ] **Employer finance view** from recruitment dashboard
- [ ] **Twig extension** for recruitment-specific helpers
- [ ] **Job offer pagination** endpoint
- [ ] **ANETI service** (external job feed integration)

### FINAL Advantages ✅
- Cleaner single-controller approach (less file sprawl)
- `ApplicationPipelineService` — consolidated pipeline logic
- `RecruitmentDashboardService` — dedicated dashboard data aggregation
- `MeetingLinkFactory` — meeting link generation for interviews

---

## 3. Formation / Learning Module

### REF Has
- **7 controllers**: AdminFormation, AdminCertificate, TrainerFormation, FormationPublic, Learning, Chatbot, FormationMl
- **6 services**: CertificatePdfGenerator, CertificateQrCode, Enrollment, FormationCertificateSignatureFormHandler, FormationChatbotAnswer, FormationProgress
- **14 entities**: Formation, FormationModule, FormationMaterial, Enrollment, LessonProgress, Certificate, FormationReview, Quiz, QuizQuestion, QuizResult, Feedback, ReviewLike + Embeddable/Money
- Review voting/like system
- Chatbot integration (OpenAI-compatible)
- ML endpoints: course recommendations, completion prediction
- Certificate: PDF generation, QR codes, verification, preview
- Admin: formation CRUD + signature preview
- Trainer: formation CRUD + modules + materials
- 3 Form types + custom validators (FormationDurationConsistent, UniqueFormationTitle)

### FINAL Has
- **1 controller** (`FormationController`) covering catalog, learning, certificates, trainer, admin — all in one
- **3 services**: Enrollment, FormationNotifier, FormationProgress
- **7 entities**: Formation, FormationModule, FormationMaterial, Enrollment, LessonProgress, Certificate, FormationReview
- Trainer students view (new in FINAL)
- Separated template structure: catalog/, learning/, certificates/, trainer/, admin/

### Missing in FINAL ❌
- [ ] **Quiz system** (Quiz, QuizQuestion, QuizResult entities + controller)
- [ ] **Feedback entity** for formation feedback
- [ ] **ReviewLike entity** (voting on reviews)
- [ ] **Chatbot integration** (FormationChatbotAnswer + ChatbotController)
- [ ] **ML endpoints** (course recommendations, completion prediction)
- [ ] **CertificatePdfGenerator** service
- [ ] **CertificateQrCodeService**
- [ ] **FormationCertificateSignatureFormHandler**
- [ ] **Custom validators** (FormationDurationConsistent, UniqueFormationTitle)
- [ ] **Form types** (FormationType, FeedbackType, FormationFormConfigurator)
- [ ] **Embeddable/Money** value object

### FINAL Advantages ✅
- `FormationNotifier` service (notification integration)
- Trainer students management view
- Better template organization (catalog/show vs flat)
- EnrollmentStatus enum

---

## 4. Finance Module

### REF Has (MASSIVE)
- **10 entities**: Contract, EscrowAccount, Invoice, Wallet, Payslip, BankAccount, Bonus, ExchangeRate, PaymentTransaction, JobReview
- **10 admin controllers**: FinanceIndex, Contract, BankAccount, Bonus, Payslip, Escrow, Disputes, Forecast, Reports, ProjectAdvancePayment
- **5 user controllers**: EmployerContract, FreelancerContract, Invoice, Escrow, Review, Stripe, Wallet + FinanceMl
- **20+ services**: ContractLifecycle, Escrow, FinanceAnalytics, FinanceForecast (AI comments, chart factory, Excel export), FinancePdf (AI summary, export), FinanceStripeClient, Invoice, Milestone, PayslipPayroll, PayslipPaymentSms, ReviewService, StripeMoney, StripeService, TwilioWhatsApp, WalletService
- **4 form types**: BankAccount, Bonus, Contract, Payslip
- Stripe integration (checkout, webhook, wallet topup)
- WhatsApp payment notifications
- SMS payment notifications
- PDF/Excel export
- AI-powered forecast comments & summaries
- Finance ML endpoints (anomaly detection, spending analysis)
- Employer/Freelancer finance dashboards with KPIs
- Dispute resolution system
- Milestone payment system
- Job review/rating system

### FINAL Has
- **5 entities**: Contract, ContractDelivery, ContractDispute, EscrowTransaction, Invoice
- **4 controllers**: Contract, Escrow, FinanceDashboard, Invoice
- **2 services**: ContractService, FinanceNotifier
- Enum-based status management (ContractStatus, DisputeStatus, EscrowTransactionType, InvoiceStatus)
- Basic contract lifecycle, escrow, invoices, disputes

### Missing in FINAL ❌
- [ ] **Wallet entity + WalletService** (balance management, topup)
- [ ] **Payslip entity + PayslipPayrollCalculator** (payroll generation)
- [ ] **BankAccount entity** (user bank accounts)
- [ ] **Bonus/Milestone entity** (milestone payments)
- [ ] **ExchangeRate entity** (multi-currency support)
- [ ] **PaymentTransaction entity** (transaction log)
- [ ] **JobReview entity + ReviewService** (post-contract reviews)
- [ ] **EscrowAccount entity** (vs. EscrowTransaction — different model)
- [ ] **Stripe integration** (StripeService, StripeController, checkout/webhook)
- [ ] **WhatsApp notifications** (TwilioWhatsAppNotifier)
- [ ] **SMS notifications** (PayslipPaymentSmsService)
- [ ] **Finance analytics** (FinanceAnalyticsService)
- [ ] **Finance forecast** (AI comments, chart factory, Excel export)
- [ ] **PDF export** (FinancePdfExportService with AI summary)
- [ ] **ML endpoints** (anomaly detection, spending analysis)
- [ ] **Admin finance panel** (10 controller classes, dashboard, CRUD for all entities)
- [ ] **Employer/Freelancer separated contracts** (EmployerContractController, FreelancerContractController)
- [ ] **Form types** (BankAccountType, BonusType, ContractType, PayslipType)
- [ ] **FinanceAllowedValues** validation

### FINAL Advantages ✅
- `ContractDelivery` entity (delivery tracking per contract)
- `ContractDispute` entity (disputes as separate entity)
- `EscrowTransaction` entity (transaction-based vs account-based — cleaner model)
- `FinanceNotifier` service (notification integration)
- Clean enum-based status management
- `FinanceDashboardController` (unified dashboard)

---

## 5. Community Module

### REF Has
- **5 controllers**: Community, CommunityApi, CommunityGroup, CommunityNetwork, CommunityMl
- **6 services**: AISummary, ContentModeration, JobConversation, Mention, Search, Translation
- **10 entities**: BlogArticle, CommunityPost, PostComment, PostLike, CommunityGroup, GroupMember, MemberInvitation, CommunityEvent, EventRsvp, CommunityNotification
- **8 form types**: BlogArticle, CommunityEvent, CommunityGroup, CommunityPost, DmMessageBody, DmStartConversation, MemberInvitation, PostComment
- Post like/unlike API
- Post commenting API
- Post translation (AI)
- Community search
- Mention autocomplete
- Community notifications (CRUD + unread count)
- Content moderation (AI)
- ML endpoints (moderate, sentiment analysis)
- Blog listing

### FINAL Has ✅ (BETTER)
- **3 controllers**: Community, CommunityNetwork, CommunitySpaces
- **1 service**: CommunityNotifier
- **9 entities**: BlogArticle, CommunityPost, CommunityComment, CommunityLike, CommunityGroup, GroupMember, MemberInvitation, CommunityEvent, EventRsvp
- `CommunityComment` entity (replaces PostComment — better naming)
- `CommunityLike` entity (replaces PostLike — better naming)
- `BlogArticleStatus` enum
- `CommunityPostStatus` enum
- `MemberInvitationStatus` enum
- **New templates**: blog/new, blog/show, events/index, events/new, events/show, feed/index, feed/new, feed/show
- Admin community moderation (approve/reject posts)
- Event CRUD (create, show, RSVP)
- Blog CRUD (create, show by slug)
- Group CRUD (create, show, join)
- Better tab-based navigation

### Missing in FINAL ❌
- [ ] **AI Summary service** (AI-powered post summaries)
- [ ] **Content Moderation service** (AI content moderation)
- [ ] **Translation service** (post translation)
- [ ] **Search service** (community search)
- [ ] **Mention service** (@ mention autocomplete)
- [ ] **JobConversation service** (job-related conversations)
- [ ] **Community notifications** entity + unread count API
- [ ] **ML endpoints** (moderate, sentiment analysis)
- [ ] **Form types** (inline forms in controller instead)
- [ ] **Community API controller** (like/comment/translate/search/mentions/notifications as JSON endpoints)
- [ ] **Post edit** page (only create/delete in FINAL)

### FINAL Advantages ✅✅
- **Better entity naming** (CommunityComment, CommunityLike)
- **Event CRUD** (full create/show/RSVP — REF had events but less UI)
- **Blog CRUD** (full create with slug-based show)
- **Enum-based statuses** (cleaner than string-based)
- **Admin moderation** panel (approve/reject)
- **Feed concept** (separate feed view with show pages)
- **CommunityNotifier** service (cross-module notification dispatch)

---

## 6. Support Module

### REF Has
- **2 controllers**: SupportClient (18 routes), SupportMl (3 routes)
- **3 services**: ChatbotService (+ Interface), SupportNotificationService
- **3 entities**: Ticket, MessageTicket, TicketAttachment
- AI-powered features: subject suggestion, text correction, triage, smart reply
- Message editing/deletion
- Ticket editing
- Feedback collection (star rating)
- PDF export per ticket
- Ticket close
- Admin ticket management with calendar view, avis (reviews)
- Chatbot for automated responses

### FINAL Has
- **1 controller**: SupportController (12 routes)
- **1 service**: SupportNotifier
- **2 entities**: SupportTicket, SupportMessage
- Ticket creation, show, messaging
- Admin support list + status management
- CSV export (admin)
- PDF export per ticket
- Status update by admin

### Missing in FINAL ❌
- [ ] **Ticket editing** (edit ticket details after creation)
- [ ] **Message editing/deletion** (edit/delete individual messages)
- [ ] **Ticket attachment** entity (file uploads on tickets)
- [ ] **Feedback/rating** system (post-resolution star rating)
- [ ] **Chatbot service** (automated responses)
- [ ] **AI subject suggestion** (auto-suggest ticket subject)
- [ ] **AI text correction** (fix grammar/spelling in ticket text)
- [ ] **ML triage** (auto-categorize/prioritize tickets)
- [ ] **ML smart reply** (AI-generated reply suggestions)
- [ ] **Admin calendar view** for tickets
- [ ] **Admin avis/reviews** section
- [ ] **Ticket close** by client

---

## 7. Messaging / DM Module

### REF Has
- DM in CommunityNetworkController (start conversation, send message, edit, delete)
- 3 entities: DmConversation, DmMessage + part of community
- Form types: DmMessageBodyType, DmStartConversationType

### FINAL Has ✅
- Dedicated `MessagingController` with own module
- 3 entities: DmConversation, DmMessage, DmParticipant
- `MessagingNotifier` service
- API endpoints for messages + unread count
- `DmParticipant` entity (many-to-many, cleaner than REF)
- Dedicated inbox template

### FINAL Advantages ✅✅
- Proper module separation (Messaging/ namespace)
- `DmParticipant` entity for proper many-to-many
- `MessagingNotifier` service
- Dedicated inbox UI
- JSON API for real-time messaging

---

## 8. Admin Panel

### REF Has
- `Admin/UserController` — Full user CRUD (list, create, edit, delete, table)
- `Admin/RecruitmentController` — Recruitment admin (list offers, show details)
- `Admin/CommunityController` — Community moderation (posts table, delete)
- `Admin/SupportTicketAdminController` — Support ticket admin
- `Admin/Finance/` — 10 controllers covering full finance admin:
  - FinanceIndex (dashboard)
  - ContractController (CRUD)
  - BankAccountController (CRUD)
  - BonusController (CRUD)
  - PayslipController (CRUD + calc preview + pay & notify + PDF export)
  - EscrowAdminController (list)
  - DisputeAdminController (list + resolve)
  - FinanceForecastController (AI forecast)
  - FinanceReportsController (reports)
  - ProjectAdvancePaymentController (advance payments)
- `Admin/Formation/` — AdminFormationController + AdminCertificateController

### FINAL Has
- Inline admin routes in module controllers (not dedicated admin controllers)
- Community admin: approve/reject posts in CommunityController
- Finance admin: basic contract show + dispute resolve in ContractController
- Formation admin: CRUD in FormationController
- Support admin: list + status in SupportController
- User admin: referenced in nav but handled by separate UserController

### Missing in FINAL ❌
- [ ] **Dedicated admin controllers** (all admin routes in module controllers — works but less organized)
- [ ] **Admin finance dashboard** (KPI overview)
- [ ] **Admin bank account CRUD**
- [ ] **Admin payslip CRUD** (with calc preview, pay & notify, PDF)
- [ ] **Admin bonus/milestone CRUD**
- [ ] **Admin escrow overview**
- [ ] **Admin dispute management** (full list + resolve)
- [ ] **Admin finance forecast** (AI-powered)
- [ ] **Admin finance reports**
- [ ] **Admin project advance payments**
- [ ] **Admin recruitment panel** (offers table, show)
- [ ] **Admin certificates** listing
- [ ] **Admin support calendar + avis**

---

## 9. AI / ML Integration

### REF Has (FULL STACK)
- **`SkiloraMlClient`** — Central HTTP client for VPS ML service
- **`AiClient`** — Generic AI client
- **`GeminiService`** — Gemini AI integration
- **`OpenAiCompatibleChatbotClient`** — Chatbot client
- **5 ML controllers** with 16 total routes:
  - `RecruitmentMlController` — match, semantic-match, analyze-cv, salary-predict, interview-questions
  - `FormationMlController` — recommend, completion-predict
  - `CommunityMlController` — moderate, sentiment
  - `FinanceMlController` — anomaly-detect, spending-analysis
  - `SupportMlController` — triage, ai-triage, smart-reply
- **Community AI services**: AISummary, ContentModeration, Translation
- **Finance AI services**: ForecastAiComment, PdfAiSummary
- **Support AI**: subject suggestion, text correction, chatbot
- **Profile AI**: bio generation (ProfileAiService — both have this)
- **2 ML dashboard templates**: recruitment/ml, formation/ml

### FINAL Has
- `ProfileAiService` (bio generation only)
- No ML client, no ML controllers, no AI services

### Missing in FINAL ❌
- [ ] **SkiloraMlClient** service (central ML API client)
- [ ] **AiClient** (generic AI client)
- [ ] **GeminiService** (Gemini integration)
- [ ] **OpenAiCompatibleChatbotClient** (chatbot)
- [ ] **All 5 ML controllers** (16 routes)
- [ ] **All community AI services** (5 services)
- [ ] **All finance AI services** (2 services)
- [ ] **ML dashboard templates** (2 templates)
- [ ] **Chatbot component** template
- [ ] **CloudinaryUploadService**

---

## 10. Testing

### REF Has (63 test files)
- Controller smoke tests (Admin, Public, Role, User)
- Entity unit tests (17 entities)
- Integration CRUD tests (6 modules: Community, Finance, Formation, Profile, Recruitment, Support)
- Service tests (12 services across Community, Finance, Formation, Recruitment, Support, User)
- Validator tests

### FINAL Has (32 test files)
- Route registration tests (6 modules)
- Controller smoke tests (Public, Role, User)
- Entity tests (12 entities)
- Integration tests (3: Community, Profile, User)
- Service tests (3)
- Recruitment-specific tests

### Missing in FINAL ❌
- [ ] **AdminSmokeTest** (admin endpoint smoke testing)
- [ ] **Finance entity tests** (BankAccount, Bonus, Contract, ExchangeRate, Payslip)
- [ ] **Finance service tests** (Escrow, Wallet)
- [ ] **Finance integration/CRUD tests**
- [ ] **Community service tests** (ContentModeration, JobConversation)
- [ ] **Formation service tests** (Enrollment, FormationProgress, Review)
- [ ] **Recruitment service tests** (HireOffer, MatchingScore)
- [ ] **Support service/integration tests**
- [ ] **Validator tests** (NoBadWords)
- [ ] **GeminiService test**
