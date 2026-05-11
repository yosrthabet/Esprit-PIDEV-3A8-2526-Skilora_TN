# Skilora — CRUD / Entities / Services / Forms Comparison

**REF** = `/Users/nyxfallagatn/ESPRIT/WEB`  
**FINAL** = `/Users/nyxfallagatn/Desktop/WEB_Final`

---

## 1. Entity Comparison

### REF: 50 entities | FINAL: 55+ entities (Updated 2025-05-09)

| Entity | REF Location | FINAL Location | Status |
|--------|-------------|----------------|--------|
| **User** | `Entity/User.php` | `Entity/User.php` | ✅ Same |
| **Profile** | `Entity/Profile.php` | `Entity/Profile.php` | ✅ Same |
| **Experience** | `Entity/Experience.php` | `Entity/Experience.php` | ✅ Same |
| **Skill** | `Entity/Skill.php` | `Entity/Skill.php` | ✅ Same |
| **PortfolioItem** | `Entity/PortfolioItem.php` | `Entity/PortfolioItem.php` | ✅ Same |
| **PasskeyCredential** | `Entity/PasskeyCredential.php` | `Entity/PasskeyCredential.php` | ✅ Same |
| **LoginHistory** | `Entity/LoginHistory.php` | `Entity/LoginHistory.php` | ✅ Same |
| **UserSession** | `Entity/UserSession.php` | `Entity/UserSession.php` | ✅ Same |
| **Notification** | `Entity/Notification.php` | `Entity/Notification.php` | ✅ Same |
| **DateRange** | `Entity/Embeddable/DateRange.php` | `Entity/Embeddable/DateRange.php` | ✅ Same |
| **Money** | `Entity/Embeddable/Money.php` | — | ❌ Missing |
| **BlameableTrait** | `Entity/Trait/BlameableTrait.php` | — | ❌ Missing |
| **TimestampableTrait** | `Entity/Trait/TimestampableTrait.php` | — | ❌ Missing |

### Recruitment Entities

| Entity | REF | FINAL | Status |
|--------|-----|-------|--------|
| **Application** | `Recruitment/Entity/Application.php` | `Recruitment/Entity/Application.php` | ✅ |
| **Company** | `Recruitment/Entity/Company.php` | `Recruitment/Entity/Company.php` | ✅ |
| **HireOffer** | `Recruitment/Entity/HireOffer.php` | `Recruitment/Entity/HireOffer.php` | ✅ |
| **JobInterview** | `Recruitment/Entity/JobInterview.php` | `Recruitment/Entity/JobInterview.php` | ✅ |
| **JobOffer** | `Recruitment/Entity/JobOffer.php` | `Recruitment/Entity/JobOffer.php` | ✅ |
| **JobPreference** | `Recruitment/Entity/JobPreference.php` | `Recruitment/Entity/JobPreference.php` | ✅ |
| **SavedJob** | `Recruitment/Entity/SavedJob.php` | `Recruitment/Entity/SavedJob.php` | ✅ |

### Formation Entities

| Entity | REF | FINAL | Status |
|--------|-----|-------|--------|
| **Formation** | `Entity/Formation.php` | `Formation/Entity/Formation.php` | 🔄 Relocated |
| **FormationModule** | `Entity/FormationModule.php` | `Formation/Entity/FormationModule.php` | 🔄 Relocated |
| **FormationMaterial** | `Entity/FormationMaterial.php` | `Formation/Entity/FormationMaterial.php` | 🔄 Relocated |
| **Enrollment** | `Entity/Enrollment.php` | `Formation/Entity/Enrollment.php` | 🔄 Relocated |
| **LessonProgress** | `Entity/LessonProgress.php` | `Formation/Entity/LessonProgress.php` | 🔄 Relocated |
| **Certificate** | `Entity/Certificate.php` | `Formation/Entity/Certificate.php` | 🔄 Relocated |
| **FormationReview** | `Entity/FormationReview.php` | `Formation/Entity/FormationReview.php` | 🔄 Relocated |
| **Quiz** | `Entity/Quiz.php` | `Formation/Entity/Quiz.php` | ✅ Implemented |
| **QuizQuestion** | `Entity/QuizQuestion.php` | `Formation/Entity/QuizQuestion.php` | ✅ Implemented |
| **QuizResult** | `Entity/QuizResult.php` | `Formation/Entity/QuizResult.php` | ✅ Implemented |
| **Feedback** | `Entity/Feedback.php` | — | ❌ Missing |
| **ReviewLike** | `Entity/ReviewLike.php` | — | ✅ Review voting added via UI |

### Finance Entities

| Entity | REF | FINAL | Status |
|--------|-----|-------|--------|
| **Contract** | `Entity/Finance/Contract.php` (309 lines) | `Finance/Entity/Contract.php` (134 lines) | ⚠️ Simpler |
| **EscrowAccount** | `Entity/Finance/EscrowAccount.php` (230 lines) | — | ❌ Missing |
| **EscrowTransaction** | — | `Finance/Entity/EscrowTransaction.php` | ✅ FINAL only |
| **Invoice** | `Entity/Finance/Invoice.php` (225 lines) | `Finance/Entity/Invoice.php` (81 lines) | ⚠️ Simpler |
| **Wallet** | `Entity/Finance/Wallet.php` (126 lines) | `Finance/Entity/Wallet.php` | ✅ Implemented |
| **Payslip** | `Entity/Finance/Payslip.php` (299 lines) | `Finance/Entity/Payslip.php` | ✅ Implemented |
| **BankAccount** | `Entity/Finance/BankAccount.php` (169 lines) | `Finance/Entity/BankAccount.php` | ✅ Implemented |
| **Bonus** | `Entity/Finance/Bonus.php` (146 lines) | `Finance/Entity/Bonus.php` | ✅ Implemented (milestone) |
| **ExchangeRate** | `Entity/Finance/ExchangeRate.php` (120 lines) | `Finance/Entity/ExchangeRate.php` | ✅ Implemented |
| **PaymentTransaction** | `Entity/Finance/PaymentTransaction.php` (248 lines) | `Finance/Entity/PaymentTransaction.php` | ✅ Implemented |
| **JobReview** | `Entity/Finance/JobReview.php` (133 lines) | `Finance/Entity/JobReview.php` | ✅ Implemented |
| **ContractDelivery** | — | `Finance/Entity/ContractDelivery.php` | ✅ FINAL only |
| **ContractDispute** | — | `Finance/Entity/ContractDispute.php` | ✅ FINAL only |

### Community Entities

| Entity | REF | FINAL | Status |
|--------|-----|-------|--------|
| **BlogArticle** | `Entity/BlogArticle.php` | `Community/Entity/BlogArticle.php` | 🔄 Relocated |
| **CommunityPost** | `Entity/CommunityPost.php` | `Community/Entity/CommunityPost.php` | 🔄 Relocated |
| **PostComment** | `Entity/PostComment.php` | — | ❌ Missing |
| **CommunityComment** | — | `Community/Entity/CommunityComment.php` | ✅ FINAL only (replaces PostComment) |
| **PostLike** | `Entity/PostLike.php` | — | ❌ Missing |
| **CommunityLike** | — | `Community/Entity/CommunityLike.php` | ✅ FINAL only (replaces PostLike) |
| **CommunityGroup** | `Entity/CommunityGroup.php` | `Community/Entity/CommunityGroup.php` | 🔄 Relocated |
| **GroupMember** | `Entity/GroupMember.php` | `Community/Entity/GroupMember.php` | 🔄 Relocated |
| **MemberInvitation** | `Entity/MemberInvitation.php` | `Community/Entity/MemberInvitation.php` | 🔄 Relocated |
| **CommunityEvent** | `Entity/CommunityEvent.php` | `Community/Entity/CommunityEvent.php` | 🔄 Relocated |
| **EventRsvp** | `Entity/EventRsvp.php` | `Community/Entity/EventRsvp.php` | 🔄 Relocated |
| **CommunityNotification** | `Entity/CommunityNotification.php` | Uses generic `Notification` entity | ✅ Via CommunityNotifier |

### Support Entities

| Entity | REF | FINAL | Status |
|--------|-----|-------|--------|
| **Ticket** | `Entity/Ticket.php` (322 lines) | — | ❌ Missing |
| **SupportTicket** | — | `Support/Entity/SupportTicket.php` (92 lines) | 🔄 Replacement (simpler) |
| **MessageTicket** | `Entity/MessageTicket.php` (172 lines) | — | ❌ Missing |
| **SupportMessage** | — | `Support/Entity/SupportMessage.php` (53 lines) | 🔄 Replacement (simpler) |
| **TicketAttachment** | `Entity/TicketAttachment.php` (134 lines) | `Support/Entity/TicketAttachment.php` | ✅ Implemented |

### Messaging Entities

| Entity | REF | FINAL | Status |
|--------|-----|-------|--------|
| **DmConversation** | `Entity/DmConversation.php` | `Messaging/Entity/DmConversation.php` | 🔄 Relocated |
| **DmMessage** | `Entity/DmMessage.php` | `Messaging/Entity/DmMessage.php` | 🔄 Relocated |
| **DmParticipant** | — | `Messaging/Entity/DmParticipant.php` | ✅ FINAL only |

---

## 2. Repository Comparison

### REF: 44 repositories | FINAL: 37 repositories (in-module)

| Area | REF Count | FINAL Count | Notes |
|------|-----------|-------------|-------|
| Core (User, Profile, etc.) | 7 | 7 | ✅ Same |
| Recruitment | 8 (+ ApplicationsTableGateway) | 7 | ⚠️ Missing gateway |
| Formation | 7 | 7 | ✅ Same (relocated) |
| Finance | 10 | 5 | ❌ Missing 5 (Wallet, Payslip, BankAccount, Bonus, ExchangeRate, PaymentTransaction, JobReview) |
| Community | 9 | 9 | ✅ Same (better named in FINAL) |
| Support | 3 | 2 | ⚠️ Missing TicketAttachmentRepository |
| Messaging | 2 | 3 | ✅ FINAL has DmParticipantRepository |
| Other (Quiz, etc.) | 3 | 0 | ❌ Missing Quiz repos |

---

## 3. Service Comparison

### REF: 48 services | FINAL: 21 services

| Service | REF | FINAL | Status |
|---------|-----|-------|--------|
| **BaseUrlResolver** | ✅ | ✅ | Same |
| **PasskeyService** | ✅ | ✅ | Same |
| **ProfileAiService** | ✅ | ✅ | Same |
| **ProfileCompletionService** | ✅ | ✅ | Same |
| **WorkspaceRedirectService** | ✅ | ✅ | Same |

### AI/ML Services (Updated)
| Service | REF | FINAL | Status |
|---------|-----|-------|--------|
| AiClient | ✅ | ❌ | Not needed (SkiloraMlClient covers all) |
| GeminiService | ✅ | ❌ | Not needed |
| SkiloraMlClient | ✅ | ✅ | `Service/AI/SkiloraMlClient.php` |
| OpenAiCompatibleChatbotClient | ✅ | ❌ | Nice-to-have |
| CloudinaryUploadService | ✅ | ✅ | `Service/Integration/CloudinaryService.php` (stub) |

### Community Services
| Service | REF | FINAL | Status |
|---------|-----|-------|--------|
| AISummaryService | ✅ | ❌ | Missing |
| ContentModerationService | ✅ | ❌ | Missing |
| JobConversationService | ✅ | ❌ | Missing |
| MentionService | ✅ | ❌ | Missing |
| SearchService | ✅ | ❌ | Missing |
| TranslationService | ✅ | ❌ | Missing |
| CommunityNotifier | ❌ | ✅ | **FINAL only** |

### Finance Services
| Service | REF | FINAL | Status |
|---------|-----|-------|--------|
| ContractLifecycleService | ✅ | ✅ | Implemented |
| ContractService | ❌ | ✅ | **FINAL only** |
| EscrowService | ✅ | ✅ | Implemented |
| FinanceAnalyticsService | ✅ | ❌ | Missing |
| FinanceForecastAiCommentService | ✅ | ❌ | Missing |
| FinanceForecastChartFactory | ✅ | ❌ | Missing |
| FinanceForecastExcelExportService | ✅ | ❌ | Missing |
| FinanceForecastService | ✅ | ❌ | Missing |
| FinancePdfAiSummaryService | ✅ | ❌ | Missing |
| FinancePdfExportService | ✅ | ❌ | Missing (CSV export available via FinanceExportService) |
| FinanceStripeClient | ✅ | ✅ | `Service/Integration/StripeService.php` |
| InvoiceService | ✅ | ✅ | Implemented |
| MilestoneService | ✅ | ✅ | Implemented |
| PayslipPayrollCalculator | ✅ | ❌ | Missing |
| PayslipPaymentSmsService | ✅ | ❌ | Missing |
| ReviewService | ✅ | ❌ | Missing |
| StripeService | ✅ | ✅ | `Service/Integration/StripeService.php` |
| TwilioWhatsAppNotifier | ✅ | ✅ | `Service/Integration/TwilioService.php` (stub) |
| WalletService | ✅ | ✅ | Implemented |
| FinanceNotifier | ❌ | ✅ | **FINAL only** |

### Formation Services
| Service | REF | FINAL | Status |
|---------|-----|-------|--------|
| CertificatePdfGenerator | ✅ | ❌ | Missing |
| CertificateQrCodeService | ✅ | ❌ | Missing |
| EnrollmentService | ✅ | ✅ | Same |
| FormationCertificateSignatureFormHandler | ✅ | ❌ | Missing |
| FormationChatbotAnswer | ✅ | ❌ | Missing |
| FormationProgressService | ✅ | ✅ | Same |
| FormationNotifier | ❌ | ✅ | **FINAL only** |

### Support Services
| Service | REF | FINAL | Status |
|---------|-----|-------|--------|
| ChatbotService | ✅ | ❌ | Missing |
| ChatbotServiceInterface | ✅ | ❌ | Missing |
| SupportNotificationService | ✅ | ❌ | Missing (replaced by SupportNotifier) |
| SupportNotifier | ❌ | ✅ | **FINAL only** |

### Recruitment Services
| Service | REF | FINAL | Status |
|---------|-----|-------|--------|
| AnetiService | ✅ | ❌ | Missing |
| ApplicationQualityScreeningService | ✅ | ❌ | Missing |
| ApplicationSubmissionService | ✅ | ✅ | Same |
| CvDocumentTextExtractor | ✅ | ❌ | Missing |
| CvJobMatchScorer | ✅ | ❌ | Missing |
| CvPdfGeneratorService | ✅ | ✅ | Same |
| EmployerApplicationService | ✅ | ❌ | Missing |
| EmployerInterviewService | ✅ | ❌ | Missing |
| HireOfferService | ✅ | ❌ | Missing |
| InterviewWhatsAppNotifier | ✅ | ❌ | Missing |
| JobOfferManager | ✅ | ❌ | Missing (replaced by EmployerJobOfferService) |
| MatchingScoreService | ✅ | ❌ | Missing (replaced by JobMatchService) |
| RecruitmentNotificationService | ✅ | ❌ | Missing |
| ApplicationPipelineService | ❌ | ✅ | **FINAL only** |
| EmployerJobOfferService | ❌ | ✅ | **FINAL only** |
| JobMatchService | ❌ | ✅ | **FINAL only** |
| MeetingLinkFactory | ❌ | ✅ | **FINAL only** |
| RecruitmentDashboardService | ❌ | ✅ | **FINAL only** |

### Other Services
| Service | REF | FINAL | Status |
|---------|-----|-------|--------|
| DashboardDataProvider | ✅ | ❌ | Missing |
| MessagingNotifier | ❌ | ✅ | **FINAL only** |
| NotificationUrlResolver | ❌ | ✅ | **FINAL only** |

---

## 4. Form Types Comparison

### REF: 18 form types | FINAL: 0 form types

| Form Type | REF | FINAL |
|-----------|-----|-------|
| BlogArticleType | ✅ | ❌ |
| CommunityEventType | ✅ | ❌ |
| CommunityGroupType | ✅ | ❌ |
| CommunityPostType | ✅ | ❌ |
| DmMessageBodyType | ✅ | ❌ |
| DmStartConversationType | ✅ | ❌ |
| MemberInvitationType | ✅ | ❌ |
| PostCommentType | ✅ | ❌ |
| BankAccountType | ✅ | ❌ |
| BonusType | ✅ | ❌ |
| ContractType | ✅ | ❌ |
| PayslipType | ✅ | ❌ |
| FeedbackType | ✅ | ❌ |
| FormationFormConfigurator | ✅ | ❌ |
| FormationType | ✅ | ❌ |
| MessageTicketType | ✅ | ❌ |
| TicketAdminType | ✅ | ❌ |
| TicketType | ✅ | ❌ |

**Note:** FINAL handles all forms inline in controllers using manual form building. The REF approach with dedicated form types is more maintainable and reusable.

---

## 5. Controller Comparison

### REF: 54 controllers | FINAL: 28 controllers

| Module | REF Controllers | FINAL Controllers |
|--------|----------------|-------------------|
| Auth | 5 | 5 |
| User/Profile | 4 | 4 |
| Dashboard | 1 | 1 |
| App/Home/Page | 3 | 3 |
| Notification | 1 | 1 |
| Dev | 1 | 1 |
| Admin User | 1 | 0 (handled inline) |
| Admin Recruitment | 1 | 0 |
| Admin Community | 1 | 0 (inline in CommunityController) |
| Admin Support | 1 | 0 (inline in SupportController) |
| Admin Finance | 10 | 0 (inline in ContractController) |
| Admin Formation | 2 | 0 (inline in FormationController) |
| Recruitment | 11 | 2 |
| Formation | 7 | 1 |
| Finance | 8 | 4 |
| Community | 5 | 3 |
| Support | 2 | 1 |
| Messaging | 0 | 1 |
| ML | 5 | 0 |

---

## 6. Enum Comparison

### REF: ~5 enums (inline/class-based) | FINAL: 14 dedicated enum files

| Enum | REF | FINAL |
|------|-----|-------|
| ApplicationStatus | inline | `Enum/ApplicationStatus.php` |
| ContractStatus | inline | `Enum/ContractStatus.php` |
| Currency | ❌ | `Enum/Currency.php` |
| DisputeStatus | ❌ | `Enum/DisputeStatus.php` |
| EscrowTransactionType | ❌ | `Enum/EscrowTransactionType.php` |
| ExperienceLevel | ❌ | `Enum/ExperienceLevel.php` |
| FeedSource | ❌ | `Enum/FeedSource.php` |
| HireOfferStatus | inline | `Enum/HireOfferStatus.php` |
| InterviewFormat | `Recruitment/InterviewFormat.php` | `Enum/InterviewFormat.php` |
| InterviewStatus | inline | `Enum/InterviewStatus.php` |
| InvoiceStatus | ❌ | `Enum/InvoiceStatus.php` |
| JobOfferStatus | inline | `Enum/JobOfferStatus.php` |
| LoginMethod | inline | `Enum/LoginMethod.php` |
| LoginStatus | inline | `Enum/LoginStatus.php` |
| TicketCategory | ❌ | `Enum/TicketCategory.php` |
| TicketPriority | ❌ | `Enum/TicketPriority.php` |
| TicketStatus | ❌ | `Enum/TicketStatus.php` |
| WorkType | `Recruitment/WorkTypeCatalog.php` | `Enum/WorkType.php` |
| BlogArticleStatus | ❌ | `Community/BlogArticleStatus.php` |
| CommunityPostStatus | ❌ | `Community/CommunityPostStatus.php` |
| MemberInvitationStatus | ❌ | `Community/MemberInvitationStatus.php` |
| EnrollmentStatus | ❌ | `Formation/EnrollmentStatus.php` |
| FormationLevel | `Formation*` | `Formation/FormationLevel.php` |
| FormationStatus | `Formation*` | `Formation/FormationStatus.php` |

**FINAL Advantage:** Much cleaner enum-based status management across all modules.

---

## 7. Validator Comparison

| Validator | REF | FINAL |
|-----------|-----|-------|
| NoBadWords | ✅ | ✅ |
| NoBadWordsValidator | ✅ | ✅ |
| FormationDurationConsistent | ✅ | ❌ |
| FormationDurationConsistentValidator | ✅ | ❌ |
| UniqueFormationTitle | ✅ | ❌ |
| UniqueFormationTitleValidator | ✅ | ❌ |
| FinanceAllowedValues (validation) | ✅ | ❌ |

---

## 8. Event Subscribers

| Subscriber | REF | FINAL |
|------------|-----|-------|
| DoctrineSessionConfigSubscriber | ✅ | ✅ |
| SecurityEventSubscriber | ✅ | ✅ |
| SecurityHeadersSubscriber | ✅ | ✅ |
| RecruitmentNoStoreCacheSubscriber | ✅ | ❌ |
| ProdDebugSubscriber | ❌ | ✅ |

---

## 9. Commands

| Command | REF | FINAL |
|---------|-----|-------|
| ImportJobFeedCommand | ✅ | ✅ |
| SpreadEmployerDemoApplicationsCommand | ✅ | ❌ |
| DatabaseConfigAuditCommand | ❌ | ✅ |
| DatabaseSessionFixCommand | ❌ | ✅ |
| SeedDefaultUsersCommand | ❌ | ✅ |

---

## 10. Tests Comparison

### REF: 63 test files | FINAL: 32 test files

| Test Area | REF | FINAL |
|-----------|-----|-------|
| Controller smoke tests | 5 | 7 (route registration tests) |
| Entity unit tests | 17 | 12 |
| Integration CRUD tests | 8 | 4 |
| Service tests | 12 | 3 |
| Validator tests | 1 | 0 |
| Finance-specific tests | 4 | 1 |

### Missing Test Coverage ❌
- Admin smoke tests
- Finance entity/service/CRUD tests
- Community service tests
- Formation service tests (Enrollment, Progress, Review)
- Recruitment service tests (HireOffer, MatchingScore)
- Support service/integration tests
- Validator tests (NoBadWords)
