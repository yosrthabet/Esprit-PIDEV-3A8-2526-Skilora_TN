# Skilora — API / Routes Comparison

**REF** = 289 route annotations | **FINAL** = ~270 route annotations (Updated 2025-05-09)  
**Gap: ~20 routes remaining** (mostly AI/forecast nice-to-haves)

---

## Route Summary by Module (Updated 2025-05-09)

| Module | REF Routes | FINAL Routes | Delta |
|--------|------------|--------------|-------|
| Auth (login, register, OAuth, 2FA, passkey) | ~12 | ~12 | 0 |
| User/Profile/Settings | ~8 | ~8 | 0 |
| Home/Pages/Dashboard | ~15 | ~15 | 0 |
| Notifications | ~3 | ~3 | 0 |
| Recruitment | ~50 | ~42 | **-8** |
| Formation | ~38 | ~45 | **+7** (quiz, ML) |
| Finance | ~35 | ~30 | **-5** |
| Community | ~30 | ~28 | **-2** |
| Support | ~21 | ~16 | **-5** |
| Messaging/DM | ~8 | ~8 | 0 |
| Admin | ~40 | ~20 | **-20** |
| AI/ML | ~16 | ~14 | **-2** |
| Finance Export | ~3 | ~3 | 0 |
| **TOTAL** | **~289** | **~270** | **-19** |

---

## 1. Routes ONLY in REF (Missing from FINAL)

### Recruitment (15 missing)
```
/employer/job-offers/page                    → Paginated job offer list
/entreprise/{id}                             → Company public profile
/employer (dashboard)                        → Employer dashboard with KPIs
/employer/finance                            → Employer finance summary in recruitment
/employer/finance/export/my-report.pdf       → PDF export employer finance
/employer/candidatures/{id}/cv               → View candidate CV (employer)
/recruitment/ml                              → ML dashboard index
/recruitment/ml/match                        → Skill match API
/recruitment/ml/semantic-match               → Semantic match API
/recruitment/ml/analyze-cv                   → CV analysis API
/recruitment/ml/salary-predict               → Salary prediction API
/recruitment/ml/interview-questions          → Interview questions API
/admin/recruitment                           → Admin recruitment index
/admin/recruitment/{id}                      → Admin recruitment show
```

### Finance (20 missing)
```
/workspace/wallet                            → Wallet overview
/workspace/wallet/recharge                   → Wallet recharge
/workspace/wallet/checkout                   → Stripe checkout session
/workspace/wallet/stripe/success             → Stripe success callback
/workspace/wallet/stripe/cancel              → Stripe cancel callback
/webhook/stripe                              → Stripe webhook
/workspace/finance                           → User finance overview
/workspace/finance/report.pdf                → Finance PDF export
/employer/finance                            → Employer finance dashboard
/mon-espace/finance                          → Freelancer finance dashboard
/employer/contrats                           → Employer contracts list
/employer/contrats/{id}                      → Employer contract detail
/employer/contrats/{id}/approve              → Approve delivery
/employer/contrats/{id}/dispute              → Raise dispute
/employer/contrats/{id}/terminate            → Terminate
/employer/contrats/{id}/fund                 → Fund escrow
/employer/contrats/{id}/milestone            → Create milestone
/employer/contrats/{id}/milestone/{}/pay     → Pay milestone
/employer/contrats/{id}/milestone/{}/cancel  → Cancel milestone
/mon-espace/contrats                         → Freelancer contracts list
/mon-espace/contrats/{id}                    → Freelancer contract detail
/mon-espace/contrats/{id}/start              → Start work
/mon-espace/contrats/{id}/deliver            → Submit delivery
/mon-espace/contrats/{id}/avis               → Freelancer review create
/employer/contrats/{id}/avis                 → Employer review create
/mon-espace/avis                             → Freelancer reviews index
/finance/ml/anomaly-detect                   → Anomaly detection API
/finance/ml/spending-analysis                → Spending analysis API
```

### Admin Finance (15 missing)
```
/admin/finance                               → Finance admin dashboard
/admin/finance/contracts                     → Contracts admin CRUD
/admin/finance/contracts/new                 → New contract
/admin/finance/contracts/{id}                → Show contract
/admin/finance/contracts/{id}/edit           → Edit contract
/admin/finance/contracts/{id}/delete         → Delete contract
/admin/finance/bank-accounts/*               → Bank accounts CRUD (5 routes)
/admin/finance/bonuses/*                     → Bonuses CRUD (5 routes)
/admin/finance/payslips/*                    → Payslips CRUD (7 routes)
/admin/finance/escrow                        → Escrow admin
/admin/finance/disputes/*                    → Disputes admin (2 routes)
/admin/finance/forecast                      → Finance forecast
/admin/finance/reports                       → Finance reports
/admin/finance/advance-payments              → Advance payments
```

### Admin Other (8 missing)
```
/admin/user                                  → User list
/admin/user/new                              → Create user
/admin/user/{id}/edit                        → Edit user
/admin/recruitment                           → Recruitment admin
/admin/recruitment/{id}                      → Show recruitment detail
/admin/community                             → Community admin (with posts table)
/admin/community/{id}/delete                 → Delete post (admin)
/admin/certificates                          → Certificates admin list
```

### Community (10 missing)
```
/community/posts/{id}/translate              → Post translation API
/community/search                            → Community search API
/community/mentions/autocomplete             → Mention autocomplete API
/community/notifications                     → Community notifications list
/community/notifications/read/{id}           → Mark notification read
/community/notifications/read-all            → Mark all read
/community/notifications/unread-count        → Unread count API
/community/groups/{id}/edit                  → Edit group
/community/groups/{id}/delete                → Delete group
/community/groups/{id}/leave                 → Leave group
/community/ml/moderate                       → Content moderation API
/community/ml/sentiment                      → Sentiment analysis API
/community/posts/{id}/edit                   → Edit post
```

### Support (9 missing)
```
/support/ai/suggest-subject                  → AI subject suggestion
/support/ai/correct-text                     → AI text correction
/support/{id}/feedback                       → Save feedback/rating
/support/{id}/close                          → Close ticket
/support/{id}/edit                           → Edit ticket
/support/{id}/messages/{}/edit               → Edit message
/support/{id}/messages/{}/delete             → Delete message
/support/ml/triage                           → ML triage API
/support/ml/ai-triage                        → AI triage API
/support/ml/smart-reply                      → AI smart reply API
/admin/support/calendar                      → Admin calendar view
/admin/support/avis                          → Admin reviews
```

### Formation (3 missing)
```
/formations/chatbot/message                  → Chatbot message API
/chatbot/ask                                 → Chatbot ask API
/formation/ml                                → ML dashboard index
/formation/ml/recommend                      → Course recommendation API
/formation/ml/completion-predict             → Completion prediction API
/admin/certificates                          → Admin certificates list
/formations/reviews/{id}/vote                → Review vote/like
```

---

## 2. Routes ONLY in FINAL (Not in REF)

### Recruitment
```
/active-offers/{id}/reopen                   → Reopen closed job offer
/active-offers/{id}/duplicate                → Duplicate job offer
/search/jobs                                 → Job search JSON API
```

### Formation
```
/trainer/formations/{id}/students            → View enrolled students
/admin/formations/{id}/publish               → Publish formation
/admin/formations/{id}/archive               → Archive formation
/admin/formations/{id}                       → Admin formation show
```

### Finance
```
/contracts/{id}/release                      → Release payment
/admin/finance (simplified)                  → Admin finance overview
/admin/finance/contracts/{id}                → Admin contract show
/admin/finance/disputes/{id}/resolve         → Admin dispute resolve
```

### Community
```
/community/posts/{id} (show)                 → Post detail page
/community/blog/new                          → Create blog article
/community/blog/{slug}                       → Blog article detail
/community/events                            → Events listing
/community/events/new                        → Create event
/community/events/{id}                       → Event detail
/community/events/{id}/rsvp                  → RSVP to event
/admin/community                             → Admin community moderation
/admin/community/posts/{id}/approve          → Approve post
/admin/community/posts/{id}/reject           → Reject post
```

### Support
```
/admin/support/export.csv                    → Admin CSV export
/support-space (legacy alias)                → Legacy support route
```

### Messaging
```
/inbox                                       → Dedicated inbox page
/inbox/{id}                                  → Conversation view
/inbox/start/{userId}                        → Start conversation
/api/inbox/{id}/messages                     → Messages JSON API
/api/inbox/{id}/send                         → Send message API
/api/inbox/unread-count                      → Unread count API
```

### Other
```
/dev/*                                       → Dev tools routes
```

---

## 3. Structurally Different Routes

| Purpose | REF Route | FINAL Route |
|---------|-----------|-------------|
| Job listing | `/offres` (CandidateJobOfferController) | `/jobs` (RecruitmentController) |
| Job apply | `/offres/{id}/postuler` | `/jobs/{id}/apply` |
| Applications | `/mon-espace/candidatures` | `/applications` |
| Employer offers | `/employer/job-offers` | `/active-offers` |
| Employer applications | `/employer/candidatures` | `/applications` (shared) |
| Employer interviews | `/employer/entretiens` | `/interviews` |
| Employer contracts | `/employer/contrats` | `/contracts` (unified) |
| Freelancer contracts | `/mon-espace/contrats` | `/contracts` (unified) |
| Invoices | `/mon-espace/factures` + `/employer/factures` | `/invoices` (unified) |
| Support index | `/support` | `/support` |
| DM | `/community/reseau` (embedded) | `/inbox` (dedicated) |
| Community posts | `/community/posts` | `/community` |
| Community groups | `/community/groups` | `/community/groups` |
| Network | `/community/reseau` | `/community/network` |
| Formations | `/formations` | `/formations` |
| Saved jobs | `/mon-espace/saved-jobs` | `/jobs/{id}/save` (inline) |
| Job preferences | `/mon-espace/preferences-emploi` | `/job-preferences` |

### Key Architectural Difference
- **REF**: French URLs with role-specific prefixes (`/mon-espace/`, `/employer/`)
- **FINAL**: English URLs with unified paths (`/contracts`, `/invoices`, `/applications`)
- **REF**: Separate controllers per role per feature
- **FINAL**: Single controller per module handling all roles
