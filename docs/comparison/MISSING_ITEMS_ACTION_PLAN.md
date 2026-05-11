# Skilora — Missing Items & Prioritized Action Plan

**REF** = `/Users/nyxfallagatn/ESPRIT/WEB`  
**FINAL** = `/Users/nyxfallagatn/Desktop/WEB_Final`

---

## Priority Legend
- 🔴 **P0 — Critical** — Core functionality broken or invisible to users
- 🟠 **P1 — High** — Major feature gaps that affect module completeness
- 🟡 **P2 — Medium** — Enhancement features that add polish
- 🟢 **P3 — Low** — Nice-to-haves, AI/ML integrations

---

## 🔴 P0 — CRITICAL (Do First)

### 1. Navigation Wiring (`layouts/_nav.html.twig`)
**Impact:** ALL features exist but are INVISIBLE to users. Navigation only shows 1-3 items per role.

**Action:** Update `_nav.html.twig` to wire all existing routes:
- **ADMIN** — Add: Formations, Recrutement, Finance, Support, Communauté links
- **EMPLOYER** — Add: Mes offres, Candidatures, Entretiens, Finance, Contrats, Communauté, Support
- **TRAINER** — Add: Mes formations, Communauté, Support
- **USER/FREELANCER** — Add: Emplois, Candidatures, Offres, Finances, Contrats, Formations, Communauté, Messagerie, Support

**Files to modify:** `templates/layouts/_nav.html.twig` (56 lines → ~108 lines)
**Effort:** 30 minutes
**Reference:** REF `templates/layouts/_nav.html.twig` (108 lines, fully wired)

---

### 2. Admin User Management Templates
**Impact:** Admin cannot manage users through the UI despite having the route.

**Action:** Create admin user templates:
- `templates/admin/user/index.html.twig` — User list with table
- `templates/admin/user/edit.html.twig` — Edit user form
- `templates/admin/user/_table.html.twig` — User table partial

**Reference:** REF `templates/admin/user/` (5 templates)
**Effort:** 2-3 hours

---

### 3. Admin Panel Templates for Existing Modules
**Impact:** Admin routes exist in controllers but render to non-existent or minimal templates.

**Action:** Create/verify admin templates for:
- `templates/admin/recruitment/index.html.twig` — Job offers admin list
- `templates/admin/recruitment/show.html.twig` — Job offer detail
- Verify `formation/admin/` templates actually render correctly
- Verify `finance/admin/` template renders correctly
- Verify `support/admin/` template renders correctly
- Verify `community/admin/` template renders correctly

**Effort:** 3-4 hours

---

## 🟠 P1 — HIGH PRIORITY

### 4. Finance Module Completion

#### 4a. Wallet System
**What:** Users need a wallet to fund escrow, receive payments, manage balance.
**Action:**
- Create `Finance/Entity/Wallet.php` (~126 lines)
- Create `Finance/Repository/WalletRepository.php`
- Create `Finance/Service/WalletService.php`
- Create `Finance/Controller/WalletController.php`
- Create `templates/finance/wallet/index.html.twig`
- Wire wallet routes into navigation

**Reference:** REF `Entity/Finance/Wallet.php`, `Service/Finance/WalletService.php`, `Controller/Finance/WalletController.php`
**Effort:** 4-6 hours

#### 4b. Employer/Freelancer Separated Finance Views
**What:** REF has role-specific views; FINAL has unified views that may not show role-appropriate data.
**Action:**
- Review `FinanceDashboardController` — ensure it renders role-appropriate KPIs
- Add employer-specific dashboard data (active contracts, pending payments, escrow balance)
- Add freelancer-specific dashboard data (earnings, pending invoices, milestones)
- Create role-specific dashboard sections or conditionals in template

**Effort:** 3-4 hours

#### 4c. Invoice Service (Auto-generation)
**What:** Invoices should auto-generate when contracts complete.
**Action:**
- Create `Finance/Service/InvoiceService.php`
- Hook into contract completion flow in `ContractService`
- Generate invoice with proper line items, amounts, dates

**Reference:** REF `Service/Finance/InvoiceService.php`
**Effort:** 2-3 hours

#### 4d. Milestone Payments
**What:** Employer should create/pay/cancel milestones per contract.
**Action:**
- Create `Finance/Entity/Milestone.php` (or Bonus equivalent)
- Create `Finance/Repository/MilestoneRepository.php`
- Add milestone routes to `ContractController`
- Add milestone section to contract show template

**Reference:** REF `Entity/Finance/Bonus.php`, `Service/Finance/MilestoneService.php`
**Effort:** 4-5 hours

---

### 5. Support Module Completion

#### 5a. Ticket Edit & Close
**Action:**
- Add edit ticket route + template
- Add close ticket route
- Add ticket editing form

**Effort:** 2 hours

#### 5b. Message Edit/Delete
**Action:**
- Add message edit route + template
- Add message delete route
- Update show template with edit/delete buttons

**Effort:** 2 hours

#### 5c. File Attachments
**Action:**
- Create `Support/Entity/TicketAttachment.php`
- Create repository
- Add file upload to ticket creation form
- Display attachments in ticket show

**Reference:** REF `Entity/TicketAttachment.php`
**Effort:** 3-4 hours

#### 5d. Feedback/Rating
**Action:**
- Add feedback fields to SupportTicket (or create separate entity)
- Add feedback form to ticket show (after resolution)
- Display feedback stats in admin

**Effort:** 2-3 hours

---

### 6. Recruitment Module Completion

#### 6a. Employer Dashboard with KPIs
**Action:**
- Create employer dashboard template with:
  - Active offers count
  - Pending applications count
  - Upcoming interviews
  - Recent hire offers
- Wire `app_employer_dashboard` route

**Reference:** REF `templates/recruitment/employer/dashboard/index.html.twig`
**Effort:** 3-4 hours

#### 6b. Company Public Profiles
**Action:**
- Ensure Company entity has enough fields (logo, description, website, etc.)
- Create public company profile template
- Link from job listings to company profile

**Reference:** REF `Controller/CompanyPublicController.php`
**Effort:** 2-3 hours

#### 6c. Application Delete (Candidate)
**Action:**
- Add delete application route in RecruitmentController
- Add delete button in applications list template

**Effort:** 30 minutes

---

### 7. Community Module Completion

#### 7a. Post Edit
**Action:**
- Add edit post route + template
- Add edit button to post show/feed

**Effort:** 1-2 hours

#### 7b. Group Edit/Delete/Leave
**Action:**
- Add edit group route + template
- Add delete group route
- Add leave group route
- Update group show template with these actions

**Effort:** 2-3 hours

#### 7c. Community Notifications
**Action:**
- Create notification tracking for community actions (likes, comments, mentions)
- API endpoints for notification list, mark read, unread count
- Consider reusing existing Notification entity or create CommunityNotification

**Reference:** REF `Entity/CommunityNotification.php`
**Effort:** 4-5 hours

---

### 8. Messaging Completion

#### 8a. Message Edit/Delete
**Action:**
- Add edit message route + inline edit UI
- Add delete message route + confirmation
- Update inbox template

**Effort:** 2 hours

---

### 9. Form Types
**What:** FINAL has 0 form types — all forms are inline in controllers. This works but is less maintainable.
**Action:** Create form types for the most commonly reused forms:
- `Formation/Form/FormationType.php`
- `Support/Form/TicketType.php`
- `Recruitment/Form/JobOfferType.php`
- `Recruitment/Form/JobApplicationType.php`
- `Community/Form/CommunityPostType.php`

**Effort:** 4-6 hours total
**Note:** Not strictly required for functionality but improves code quality.

---

## 🟡 P2 — MEDIUM PRIORITY

### 10. Formation Enhancements

#### 10a. Quiz System
**Action:**
- Create `Formation/Entity/Quiz.php`, `QuizQuestion.php`, `QuizResult.php`
- Create quiz CRUD in FormationController
- Add quiz taking UI for students
- Auto-grade and record results

**Reference:** REF entities (129 + 158 + 144 lines)
**Effort:** 8-10 hours

#### 10b. Formation Review Voting
**Action:**
- Create `Formation/Entity/ReviewLike.php`
- Add vote endpoint in FormationController
- Add thumbs up/down UI in formation show

**Effort:** 2 hours

#### 10c. Custom Validators
**Action:**
- Create `FormationDurationConsistent` validator
- Create `UniqueFormationTitle` validator
- Apply to formation form handling

**Reference:** REF validators
**Effort:** 2 hours

---

### 11. Finance Enhancements

#### 11a. Stripe Integration
**Action:**
- Create `Finance/Service/StripeService.php`
- Create `Finance/Controller/StripeController.php`
- Add Stripe checkout for wallet topup
- Add Stripe webhook handler
- Add env vars: STRIPE_SECRET_KEY, STRIPE_PUBLIC_KEY, STRIPE_WEBHOOK_SECRET

**Reference:** REF `Service/Finance/StripeService.php`, `Controller/Finance/StripeController.php`
**Effort:** 6-8 hours

#### 11b. Payslip System
**Action:**
- Create `Finance/Entity/Payslip.php`
- Create payroll calculator service
- Add admin CRUD for payslips
- Add PDF export

**Effort:** 8-10 hours

#### 11c. Bank Account CRUD
**Action:**
- Create `Finance/Entity/BankAccount.php`
- Create admin CRUD
- Wire to user profile

**Effort:** 4-5 hours

#### 11d. Exchange Rates
**Action:**
- Create `Finance/Entity/ExchangeRate.php`
- Create rate management admin
- Apply currency conversion in finance calculations

**Effort:** 3-4 hours

#### 11e. Payment Transaction Log
**Action:**
- Create `Finance/Entity/PaymentTransaction.php`
- Log all financial operations
- Display in admin finance reports

**Effort:** 4-5 hours

#### 11f. Job Review/Rating System
**Action:**
- Create `Finance/Entity/JobReview.php`
- Add review form post-contract completion
- Display reviews on profiles

**Reference:** REF `Entity/Finance/JobReview.php`, `Service/Finance/ReviewService.php`
**Effort:** 4-5 hours

---

### 12. Admin Finance Dashboard & CRUD
**Action:**
- Create admin finance index template with KPIs
- Create admin contract CRUD templates
- Create admin escrow overview template
- Create admin dispute management templates
- Create admin reports template

**Effort:** 8-10 hours

---

### 13. Additional Templates & Partials

#### 13a. Support Admin Enhancements
- Admin calendar view
- Admin avis/reviews
- Status change email template

#### 13b. Recruitment Partials
- Interview countdown script component
- Candidate/employer base layouts
- Status actions partial

#### 13c. Finance Form Layout
- `form/finance_form_layout.html.twig`
- `form/formation_form_layout.html.twig`

**Effort:** 4-6 hours total

---

### 14. Entity Traits
**Action:**
- Create `Entity/Trait/BlameableTrait.php` (created_by, updated_by tracking)
- Create `Entity/Trait/TimestampableTrait.php` (created_at, updated_at)
- Create `Entity/Embeddable/Money.php` (amount + currency value object)
- Apply traits to relevant entities

**Effort:** 2-3 hours

---

### 15. Testing Coverage
**Action:** Add missing test files:
- Admin smoke tests
- Finance entity tests (Contract, Invoice, Escrow)
- Finance service tests
- Community service tests
- Formation service tests
- Support integration tests
- Validator tests

**Effort:** 8-12 hours

---

## 🟢 P3 — LOW PRIORITY (AI/ML & Advanced)

### 16. AI/ML Integration
**Action:**
- Create `Service/AI/SkiloraMlClient.php`
- Create `Service/AI/AiClient.php`
- Create `Service/AI/GeminiService.php`
- Create ML controllers (Recruitment, Formation, Community, Finance, Support)
- Create ML dashboard templates (recruitment, formation)
- Add chatbot component + service
- Add AI-powered features to support (triage, smart reply, subject suggestion)
- Add AI-powered community features (moderation, sentiment, translation, summary)
- Add AI-powered finance features (forecast, anomaly detection, spending analysis)

**Effort:** 20-30 hours total
**Dependency:** VPS ML service at `http://164.90.212.158:8000`

---

### 17. WhatsApp/SMS Notifications
**Action:**
- Create `Service/TwilioWhatsAppNotifier.php`
- Integrate for interview scheduling
- Integrate for payment confirmations
- Add Twilio env vars

**Effort:** 4-6 hours
**Dependency:** Twilio account + API keys

---

### 18. Cloudinary Upload Integration
**Action:**
- Create `Service/CloudinaryUploadService.php`
- Replace local file uploads with cloud-hosted
- Add Cloudinary env vars

**Effort:** 3-4 hours
**Dependency:** Cloudinary account

---

### 19. Mercure Real-time Updates
**Action:**
- Configure Mercure hub
- Add real-time updates to messaging
- Add real-time notifications
- Add real-time support ticket updates

**Effort:** 6-8 hours
**Dependency:** Mercure hub setup

---

### 20. Finance PDF/Excel Exports
**Action:**
- Create `FinancePdfExportService`
- Create `FinanceForecastExcelExportService`
- Add export routes in finance controllers
- Generate professional report PDFs

**Effort:** 6-8 hours

---

## Implementation Status (Updated 2025-05-09)

| Priority | Items | Status |
|----------|-------|--------|
| 🔴 P0 — Critical | 3 items | ✅ ALL DONE |
| 🟠 P1 — High | 9 items | ✅ ALL DONE |
| 🟡 P2 — Medium | 6 items | ✅ ALL DONE |
| 🟢 P3 — Low | 5 items | ✅ ALL DONE (stubs for ext. services) |
| **TOTAL** | **23 items** | **✅ COMPLETE** |

### Remaining ❌ (minor gaps)
- ANETI external job feed import (external API, not applicable)

### Verification Results (Final — 2025-05-09)
- PHPStan: 0 errors (level 5)
- Twig lint: 201 templates valid
- Container lint: All services compatible

### New items implemented in final pass
- ✅ FormationFeedback entity
- ✅ FinanceAnalyticsService + forecast + monthly breakdown
- ✅ FinanceForecastExcelExportService + export route
- ✅ ChatbotController + floating widget (base layout)
- ✅ PayslipPdfService + admin PDF export route
- ✅ PayslipPayrollCalculator
- ✅ ReviewService + ReviewController (submit review + admin CRUD)
- ✅ ContentModerationService (wraps ML client)
- ✅ AiSummaryService (wraps ML client)
- ✅ Post translation via CommunityMlController /translate
- ✅ Community search (repo + route + API)
- ✅ Mention autocomplete API (/community/mentions)
- ✅ Admin support calendar view (FullCalendar)
- ✅ Status change email (SupportNotifier + MailerInterface)
- ✅ CvEducationType + CvExperienceType form types
- ✅ FinanceAnalyticsController + dashboard template (Chart.js)
- ✅ Nav links: Calendrier, Avis, Chatbot, Analytique

---

## Quick Wins (< 1 hour each) — ✅ ALL DONE

1. ✅ **Wire navigation** — `AppExtension.php` with full role-based nav
2. ✅ **Add post edit route** in CommunityController
3. ✅ **Add application delete** route in RecruitmentController
4. ✅ **Add ticket close** route in SupportController
5. ✅ **Add group leave/delete** routes in CommunitySpacesController
6. ✅ **Add message edit/delete** routes in MessagingController

---

## FINAL Project Advantages Over REF (Keep These!)

| Feature | Why It's Better |
|---------|-----------------|
| **Modular namespace structure** | `Community/`, `Finance/`, `Formation/`, `Messaging/`, `Recruitment/`, `Support/` — cleaner than flat |
| **Enum-based statuses** | 14 dedicated enums vs inline strings — type-safe |
| **CommunityComment/CommunityLike** | Better entity naming |
| **DmParticipant entity** | Proper many-to-many for conversations |
| **MessagingNotifier** | Dedicated notification dispatch |
| **FinanceNotifier** | Dedicated notification dispatch |
| **FormationNotifier** | Dedicated notification dispatch |
| **CommunityNotifier** | Dedicated notification dispatch |
| **SupportNotifier** | Dedicated notification dispatch |
| **ContractDelivery entity** | Delivery tracking per contract |
| **ContractDispute entity** | Disputes as first-class entity |
| **EscrowTransaction** | Transaction-based model (vs account-based) |
| **Event CRUD** | Full events with RSVP |
| **Blog CRUD** | Create + show by slug |
| **Feed with show pages** | Individual post pages |
| **Admin post moderation** | Approve/reject workflow |
| **Trainer students view** | See enrolled students |
| **Formation publish/archive** | Admin lifecycle management |
| **Job reopen/duplicate** | Employer convenience features |
| **Database seed command** | Quick dev setup |
| **Database audit command** | Schema validation |
| **Admin CSV export** | Support ticket export |
| **Unified finance views** | Less template duplication |
| **NotificationUrlResolver** | Cross-module notification URLs |
