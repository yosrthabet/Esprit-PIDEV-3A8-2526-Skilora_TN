# Skilora — UI / Templates Comparison

**REF** = `/Users/nyxfallagatn/ESPRIT/WEB`  
**FINAL** = `/Users/nyxfallagatn/Desktop/WEB_Final`

---

## Template Count (Updated 2025-05-09)

| Area | REF | FINAL | Status |
|------|-----|-------|--------|
| **Total templates** | 230 | 195 | ⚠️ ~85% parity |
| **Components** | 60 | 62 | ✅ FINAL has more (interview countdown, status badge) |
| **Layouts** | 5 | 5 | ✅ Same |
| **Auth** | 6 | 6 | ✅ Same |
| **Home** | 8 | 8 | ✅ Same |
| **Pages** | 7 | 7 | ✅ Same |
| **Dashboard** | 4 | 3 | ⚠️ Unified dashboard with role conditionals |
| **Admin** | 35 | 10+ | ⚠️ Users, recruitment, finance, certificates covered |
| **Recruitment** | 25 | 17 | ⚠️ ML dashboard added |
| **Formation** | 18 | 22 | ✅ Quiz + ML dashboards + admin |
| **Finance** | 18 | 14 | ⚠️ Admin KPIs, disputes, wallet, invoices covered |
| **Community** | 12 | 14 | ✅ FINAL has MORE |
| **Support** | 15 | 8 | ⚠️ Core templates covered |
| **Certificate** | 7 | 7 | ✅ Same (different path) |
| **Messaging** | 0 | 1 | ✅ FINAL has dedicated inbox |
| **ML Dashboards** | 2 | 2 | ✅ Recruitment + Formation ML |
| **Form Layouts** | 0 | 2 | ✅ Finance + formation form layouts |

---

## 1. Navigation (`layouts/_nav.html.twig`)

### REF Navigation (108 lines, 4 roles fully wired)

**ADMIN nav** (14 items):
- Tableau de bord, Utilisateurs
- Recrutement, Formations, Certificats
- Finance, Contrats, Litiges, Escrow
- Support, Avis
- Communauté

**EMPLOYER nav** (13 items):
- Dashboard, Mes offres, Publier une offre, Candidatures, Entretiens, Offres d'embauche
- Finance, Portefeuille, Contrats, Factures, Escrow
- Communauté, Support

**TRAINER nav** (4 items):
- Dashboard, Mes formations, Communauté, Support

**USER/FREELANCER nav** (15 items):
- Accueil, Trouver un emploi, Mes candidatures, Offres d'embauche, Offres sauvegardées, Préférences emploi
- Mes finances, Portefeuille, Mes contrats, Factures, Escrow
- Formations, Mes formations, Certificats
- Communauté, Réseau, Support

### FINAL Navigation (56 lines, skeletal)

**ADMIN nav** (2 items):
- Tableau de bord, Utilisateurs

**TRAINER nav** (1 item):
- Tableau de bord

**EMPLOYER nav** (1 item):
- Tableau de bord

**USER/FREELANCER nav** (3 items):
- Accueil, Profil, Notifications

### ❌ CRITICAL: FINAL navigation is nearly empty
The FINAL project has all the routes and controllers but the navigation sidebar/menu only shows 1-3 items per role. Users cannot discover any features through the UI.

**Action Required:** Wire ALL existing module routes into the navigation.

---

## 2. Dashboard Templates

### REF Has
- `dashboard/admin.html.twig` — Admin KPI dashboard
- `dashboard/finance.html.twig` — Finance-specific dashboard
- `dashboard/freelancer.html.twig` — Freelancer workspace
- `dashboard/trainer.html.twig` — Trainer workspace

### FINAL Has
- `dashboard/admin.html.twig`
- `dashboard/freelancer.html.twig`
- `dashboard/trainer.html.twig`

### Missing ❌
- [ ] `dashboard/finance.html.twig` — Finance dashboard template

---

## 3. Admin Templates

### REF Has (35 templates)

**Admin User:**
- `admin/user/index.html.twig` — User list with table
- `admin/user/new.html.twig` — Create user
- `admin/user/edit.html.twig` — Edit user
- `admin/user/_form.html.twig` — User form partial
- `admin/user/_table.html.twig` — User table partial

**Admin Recruitment:**
- `admin/recruitment/index.html.twig` — Job offers list
- `admin/recruitment/show.html.twig` — Job offer detail
- `admin/recruitment/_offers_table.html.twig` — Offers table partial

**Admin Formation:**
- `admin/formation/index.html.twig` — Formation list
- `admin/formation/new.html.twig` — Create formation
- `admin/formation/edit.html.twig` — Edit formation
- `admin/formation/_form.html.twig` — Formation form
- `admin/formation/_dialog_form.html.twig` — Dialog form

**Admin Finance (15 templates):**
- `admin/finance/index.html.twig` — Finance dashboard
- `admin/finance/layout.html.twig` — Finance admin layout
- `admin/finance/contract/` — index, show, form
- `admin/finance/bank_account/` — index, show, form
- `admin/finance/bonus/` — index, show, form
- `admin/finance/payslip/` — index, show, form
- `admin/finance/escrow/index.html.twig`
- `admin/finance/disputes/` — index, show
- `admin/finance/forecast/index.html.twig`
- `admin/finance/reports/index.html.twig`
- `admin/finance/project_payment/index.html.twig`
- Various macros and action partials

**Admin Community:**
- `admin/community/index.html.twig` — Posts moderation
- `admin/community/_posts_table.html.twig` — Posts table

**Admin Certificates:**
- `admin/certificates/index.html.twig`

### FINAL Has (1 template)
- `admin/formation/_signature_zone_script.html.twig` — Only a script partial

### Missing ❌ (CRITICAL)
- [ ] **ALL admin user templates** (list, create, edit, form, table)
- [ ] **ALL admin recruitment templates** (list, show, table)
- [ ] **ALL admin formation templates** (list, create, edit, form, dialog)
- [ ] **ALL admin finance templates** (15 templates)
- [ ] **Admin community templates** (posts moderation table)
- [ ] **Admin certificates template**

**Note:** FINAL has admin routes in controllers (community admin, formation admin, finance admin) but **no dedicated admin templates** — the controllers likely render inline or use module templates with admin conditionals.

---

## 4. Recruitment Templates

### REF Has (25 templates)

**Candidate area:**
- `recruitment/candidate/base.html.twig` — Base layout
- `recruitment/candidate/job_offer/index.html.twig` — Job listing
- `recruitment/candidate/job_offer/show.html.twig` — Job detail
- `recruitment/candidate/job_offer/apply.html.twig` — Apply form
- `recruitment/candidate/applications/index.html.twig` — My applications
- `recruitment/candidate/saved_jobs/index.html.twig` — Saved jobs
- `recruitment/candidate/preferences/index.html.twig` — Job preferences
- `recruitment/candidate/offers/index.html.twig` — Hire offers

**Employer area:**
- `recruitment/employer/base.html.twig` — Base layout
- `recruitment/employer/dashboard/index.html.twig` — Dashboard with KPIs
- `recruitment/employer/job_offer/` — index, show, form, _card, _cards
- `recruitment/employer/applications/` — index, profile, cover_letter, _status_actions
- `recruitment/employer/interview/` — index, form
- `recruitment/employer/offers/` — index, create
- `recruitment/employer/finance/index.html.twig` — Finance summary

**Company & ML:**
- `recruitment/company/public_profile.html.twig`
- `recruitment/components/_interview_countdown_script.html.twig`
- `recruitment/ml/index.html.twig` — ML dashboard (5-tab)

**CV Builder:**
- `recruitment/cv/builder.html.twig`
- `recruitment/cv/pdf/classic.html.twig`
- `recruitment/cv/pdf/modern.html.twig`

### FINAL Has (15 templates)

**Jobs (unified):**
- `recruitment/jobs/index.html.twig` — Job listing
- `recruitment/jobs/show.html.twig` — Job detail
- `recruitment/jobs/apply.html.twig` — Apply form
- `recruitment/jobs/_card.html.twig` — Job card partial

**Applications (unified):**
- `recruitment/applications/index.html.twig` — Applications list
- `recruitment/applications/show.html.twig` — Application detail

**Employer:**
- `recruitment/employer/offers.html.twig` — Active offers
- `recruitment/employer/show.html.twig` — Offer detail
- `recruitment/employer/post_job.html.twig` — Create offer
- `recruitment/employer/offer_form.html.twig` — Offer form
- `recruitment/employer/applications.html.twig` — Applications
- `recruitment/employer/profile.html.twig` — Candidate profile view
- `recruitment/employer/interview_form.html.twig` — Interview form

**Other:**
- `recruitment/offers/index.html.twig` — Hire offers
- `recruitment/preferences/index.html.twig` — Preferences
- `recruitment/interviews/index.html.twig` — Interviews
- `recruitment/cv/builder.html.twig` — CV builder
- `recruitment/cv/pdf/classic.html.twig`, `modern.html.twig`

### Missing in FINAL ❌
- [ ] **Employer dashboard** with KPIs
- [ ] **Employer finance summary** page
- [ ] **Saved jobs page** (route exists but template unclear)
- [ ] **Company public profile** page
- [ ] **Interview countdown script** component
- [ ] **ML dashboard** (5-tab AI tools)
- [ ] **Cover letter view** (employer viewing candidate cover letter)
- [ ] **Status actions partial** for applications
- [ ] **Candidate base layout** and **Employer base layout** (dedicated role layouts)

---

## 5. Finance Templates

### REF Has (18 templates)

**Role-specific contracts:**
- `finance/contract/employer_index.html.twig`
- `finance/contract/employer_show.html.twig`
- `finance/contract/freelancer_index.html.twig`
- `finance/contract/freelancer_show.html.twig`

**Dashboards:**
- `finance/dashboard/employer.html.twig`
- `finance/dashboard/freelancer.html.twig`

**Invoices:**
- `finance/invoice/employer_list.html.twig`
- `finance/invoice/freelancer_list.html.twig`
- `finance/invoice/show.html.twig`

**Other:**
- `finance/escrow/index.html.twig`
- `finance/wallet/index.html.twig`
- `finance/report/employee_report.html.twig`
- `finance/report/payslip_slip.html.twig`
- `finance/review/form.html.twig`
- `finance/review/my_reviews.html.twig`
- `form/finance_form_layout.html.twig`

### FINAL Has (7 templates)

- `finance/admin/index.html.twig` — Admin finance overview
- `finance/contracts/index.html.twig` — Unified contracts list
- `finance/contracts/show.html.twig` — Contract detail
- `finance/dashboard/index.html.twig` — Unified dashboard
- `finance/escrow/index.html.twig` — Escrow overview
- `finance/invoices/index.html.twig` — Unified invoices list
- `finance/invoices/show.html.twig` — Invoice detail

### Missing in FINAL ❌
- [ ] **Employer-specific contract views** (employer_index, employer_show)
- [ ] **Freelancer-specific contract views** (freelancer_index, freelancer_show)
- [ ] **Employer-specific dashboard** with KPIs
- [ ] **Freelancer-specific dashboard** with earnings
- [ ] **Employer-specific invoice list**
- [ ] **Freelancer-specific invoice list**
- [ ] **Wallet page** (balance, topup, transaction history)
- [ ] **Reports** (employee report, payslip slip)
- [ ] **Review/rating forms** (post-contract reviews)
- [ ] **Finance form layout**

### FINAL Advantage ✅
- Unified templates (single contracts/index, invoices/index) — less duplication
- Dedicated admin finance index

---

## 6. Community Templates

### REF Has (12 templates)
- `community/index.html.twig` — Main community page
- `community/_subnav.html.twig` — Sub-navigation
- `community/blog/index.html.twig` — Blog listing
- `community/posts/index.html.twig` — Posts feed
- `community/posts/edit.html.twig` — Edit post
- `community/groups/index.html.twig` — Groups listing
- `community/groups/show.html.twig` — Group detail
- `community/groups/form.html.twig` — Group form
- `community/network/index.html.twig` — Network/DM
- `community/network/edit_message.html.twig` — Edit DM
- `community/notifications/index.html.twig` — Community notifications

### FINAL Has (14 templates) ✅ MORE
- `community/_tabs.html.twig` — Tab navigation
- `community/admin/index.html.twig` — Admin moderation
- `community/blog/index.html.twig` — Blog listing
- `community/blog/new.html.twig` — **Create blog article** ✅ NEW
- `community/blog/show.html.twig` — **Blog article show** ✅ NEW
- `community/events/index.html.twig` — **Events listing** ✅ NEW
- `community/events/new.html.twig` — **Create event** ✅ NEW
- `community/events/show.html.twig` — **Event detail** ✅ NEW
- `community/feed/index.html.twig` — **Feed listing** ✅ NEW
- `community/feed/new.html.twig` — **Create feed post** ✅ NEW
- `community/feed/show.html.twig` — **Feed post detail** ✅ NEW
- `community/groups/index.html.twig` — Groups listing
- `community/groups/new.html.twig` — **Create group** ✅ NEW
- `community/groups/show.html.twig` — Group detail
- `community/network/index.html.twig` — Network

### FINAL Advantages ✅✅
- **Blog full CRUD** (create + show by slug vs. listing only in REF)
- **Events full CRUD** (create, show, RSVP — completely new)
- **Feed with show pages** (individual post pages with comments)
- **Tab-based navigation** (cleaner than subnav)
- **Admin moderation** page
- **Group creation** UI

### Missing in FINAL ❌
- [ ] **Post edit** (REF has posts/edit.html.twig)
- [ ] **DM message edit** (REF has network/edit_message.html.twig)
- [ ] **Community notifications** page

---

## 7. Support Templates

### REF Has (15 templates)
- `support/client/index.html.twig` — Client ticket list
- `support/client/new.html.twig` — Create ticket (with AI triage preview)
- `support/client/show.html.twig` — Ticket detail with messaging
- `support/client/edit.html.twig` — Edit ticket
- `support/client/edit_message.html.twig` — Edit message
- `support/client/pdf_export.html.twig` — PDF export
- `support/client/_dialog_form.html.twig` — Dialog form
- `support/client/_ticket_grid.html.twig` — Ticket grid
- `support/admin/index.html.twig` — Admin ticket list
- `support/admin/show.html.twig` — Admin ticket detail (with AI smart reply)
- `support/admin/calendar.html.twig` — Admin calendar view
- `support/admin/avis.html.twig` — Admin reviews
- `support/admin/edit_message.html.twig` — Edit message (admin)
- `support/admin/pdf_export.html.twig` — Admin PDF
- `support/admin/_ticket_table.html.twig`, `_message_list.html.twig`, `_avis_grid.html.twig`
- `support/emails/status_change.html.twig` — Status change email

### FINAL Has (5 templates)
- `support/admin/index.html.twig` — Admin ticket list
- `support/client/index.html.twig` — Client ticket list
- `support/client/new.html.twig` — Create ticket
- `support/show.html.twig` — Ticket detail (shared)
- `support/pdf/ticket.html.twig` — PDF export

### Missing in FINAL ❌
- [ ] **Ticket edit** template
- [ ] **Message edit** template (client + admin)
- [ ] **Admin ticket show** (with AI smart reply button)
- [ ] **Admin calendar** view
- [ ] **Admin avis/reviews** page
- [ ] **Client PDF export** template
- [ ] **Dialog form** for tickets
- [ ] **Ticket grid** partial
- [ ] **Ticket table**, **Message list**, **Avis grid** partials
- [ ] **Status change email** template
- [ ] **AI triage preview** in ticket creation
- [ ] **AI smart reply** button in admin

---

## 8. Shared Components

### Both Have (59 components) ✅
All shadcn/ui-style Twig components are identical:
accordion, alert, alert-dialog, aspect-ratio, avatar, badge, breadcrumb, button, calendar, card, carousel, checkbox, collapsible, combobox, command, context-menu, date-picker, dialog, drawer, dropdown-menu, edit-dialog, empty-state, file-upload, form-field, form, hand-gesture, hover-card, icon, input, label, menubar, navigation-menu, number-input, otp-input, pagination, password-input, popover, progress, radio-group, resizable, scroll-area, select, separator, sheet, sidebar, skeleton, slider, spinner, star-rating, switch, table, tabs, textarea, toast, toggle, toggle-group, tooltip, + icons (6), + ui components (3: banner_hero, data_list, stat_card)

### REF Only
- `components/_chatbot.html.twig` — Floating chatbot widget

### Missing in FINAL ❌
- [ ] `components/_chatbot.html.twig` — Chatbot floating widget

---

## 9. Other Template Differences

### Emails
| Template | REF | FINAL |
|----------|-----|-------|
| `emails/verify_email.html.twig` | ✅ | ✅ |
| `emails/welcome.html.twig` | ✅ | ✅ |
| `support/emails/status_change.html.twig` | ✅ | ❌ |

### Form Layouts
| Template | REF | FINAL |
|----------|-----|-------|
| `form/finance_form_layout.html.twig` | ✅ | ❌ |
| `form/formation_form_layout.html.twig` | ✅ | ❌ |

### Messaging
| Template | REF | FINAL |
|----------|-----|-------|
| `messaging/inbox/index.html.twig` | ❌ | ✅ NEW |

### Notifications
| Template | REF | FINAL |
|----------|-----|-------|
| `notifications/index.html.twig` | ✅ | ✅ |
