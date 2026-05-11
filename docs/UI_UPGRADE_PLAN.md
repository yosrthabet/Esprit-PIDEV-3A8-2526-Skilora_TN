# Skilora — Full-Scale UI Upgrade Plan

**Goal:** Transform every dashboard and flow page into a cohesive, advanced, professional-grade UI with consistent design language, proper modals/dialogs, smooth transitions, breadcrumbs, and polished system flows.

---

## Current State Assessment

### Design System Already In Place
- **Glass-panel system** — `glass-panel` class with frosted glass effect
- **Holographic typography** — `tracking-holographic`, `font-light` headings
- **Tailwind CSS** + **Lucide icons** + **Alpine.js** for interactivity
- **Dark/light mode** — theme toggle, `dark:` variant classes
- **Spatial UI** — radial gradient overlays, backdrop-blur, subtle animations
- **Component library** — 50+ Twig components in `templates/components/`

### What's Strong (keep as-is)
- ✅ **Admin dashboard** — polished bento grid, Chart.js, stat cards with trends
- ✅ **Freelancer dashboard** — employer/freelancer dual mode, hero section, signals
- ✅ **Job search** — filters, source pills, match scores
- ✅ **Support admin** — filters, stats, CSV export
- ✅ **Auth pages** — modern login/register/2FA flow

### What Needs Upgrade

| Page/Flow | Issues |
|-----------|--------|
| **Trainer dashboard** | Minimal — no charts, no stat icons, no quick actions, no formations progress |
| **Finance admin pages** (payslips, exchange rates) | Raw single-line forms, no table structure, no PDF download buttons, no modals |
| **Finance analytics** | Newly created, functional but basic — needs KPI icons, better chart layout |
| **Community feed** | No search bar visible, no avatar initials, no skeleton loading, no infinite scroll |
| **Community groups/events** | No cover images, no member counts in cards, bare forms |
| **Support client pages** | No ticket progress timeline, no status badges with color |
| **Support show** | Missing conversation thread UI (chat bubbles) |
| **Admin calendar** | Basic FullCalendar embed, no header stats |
| **Admin reviews** | Functional table, needs stat header + filters |
| **Chatbot** | Basic iframe embed, needs real inline chat widget |
| **Notifications** | No grouping, no "mark all read", no empty state illustration |
| **CV Builder** | Form-heavy, no live preview panel |
| **User profile** | No public profile card variant, no stats section |
| **All forms** | Most use raw `<input>` — should use consistent form components |
| **All delete actions** | Raw `confirm()` — should use `alert-dialog` component |
| **Breadcrumbs** | Missing on most subpages |
| **Loading states** | No skeleton loaders anywhere |
| **Empty states** | Inconsistent — some plain text, some have illustrations |
| **Quick action links** | Admin dashboard has `#` placeholder links |

---

## Upgrade Phases

### Phase 1: Foundation & Consistency (HIGH)
_Shared patterns that improve every page at once_

| # | Task | Files | Effort |
|---|------|-------|--------|
| 1.1 | **Breadcrumb partial** — Create `_breadcrumbs.html.twig`, include in all subpages | `components/_breadcrumbs.html.twig` + all templates | M |
| 1.2 | **Confirm delete modal** — Replace all `confirm()` with Alpine `alert-dialog` | All delete forms (~15 places) | M |
| 1.3 | **Status badges** — Unified `_status_badge.html.twig` with color map for all enums | `components/_status_badge.html.twig` | S |
| 1.4 | **Empty state component** — Illustration + message + CTA button partial | `components/_empty_state.html.twig` | S |
| 1.5 | **Skeleton loader** — CSS-only shimmer partial for tables and cards | `components/_skeleton.html.twig` | S |
| 1.6 | **Form input consistency** — Standardize all `<input>`, `<select>`, `<textarea>` styling | Global CSS or Twig form theme | M |
| 1.7 | **Toast notifications** — Wire flash messages to slide-in toast with icon variants | `base.html.twig` toast container (already exists, verify) | S |
| 1.8 | **Fix admin dashboard quick action `#` links** — point to real routes | `dashboard/admin.html.twig` | S |

### Phase 2: Dashboard Overhaul (HIGH)
_Each role gets a polished, data-rich command center_

| # | Task | Files | Effort |
|---|------|-------|--------|
| 2.1 | **Trainer dashboard upgrade** — Add stat icons, Chart.js enrollment chart, upcoming modules, student progress ring, quick actions panel | `dashboard/trainer.html.twig` | L |
| 2.2 | **Admin dashboard — fix links & add activity feed** — Real quick action routes, recent tickets list, recent disputes | `dashboard/admin.html.twig` | M |
| 2.3 | **Finance dashboard upgrade** — Add KPI icon badges, mini chart sparklines, escrow breakdown donut, recent transactions list | `finance/dashboard/index.html.twig` | L |
| 2.4 | **Finance analytics page** — Add KPI stat cards with icons/trends, better chart cards with glass style | `finance/analytics/index.html.twig` | M |

### Phase 3: Admin Panel Polish (MEDIUM)
_Tables, CRUD pages, modals for admin operations_

| # | Task | Files | Effort |
|---|------|-------|--------|
| 3.1 | **Payslips admin** — Full table with columns, PDF download button per row, create modal dialog instead of inline form | `finance/admin/payslips.html.twig` | M |
| 3.2 | **Exchange rates admin** — Table layout, create modal, delete button with confirm | `finance/admin/exchange_rates.html.twig` | M |
| 3.3 | **Reviews admin** — Add stat header (avg rating, total count), filter by rating | `finance/admin/reviews.html.twig` | M |
| 3.4 | **Admin calendar** — Add stat header (tickets this month, urgent count), color legend | `support/admin/calendar.html.twig` | S |
| 3.5 | **Admin community** — Add post preview modal, bulk actions (approve/reject) | `community/admin/index.html.twig` | M |

### Phase 4: Community & Social UX (MEDIUM)
_Make the social layer feel like a modern platform_

| # | Task | Files | Effort |
|---|------|-------|--------|
| 4.1 | **Community feed** — Add search bar at top, avatar initials for authors, post card hover effects, inline translate button | `community/feed/index.html.twig` | M |
| 4.2 | **Post show** — Chat-bubble style comment thread, like animation, share button | `community/feed/show.html.twig` | M |
| 4.3 | **Groups** — Cover image hero, member avatars row, join/leave modal | `community/groups/show.html.twig` | M |
| 4.4 | **Events** — Calendar-style date badge, attendee count, RSVP button with animation | `community/events/show.html.twig` | M |
| 4.5 | **Network** — User cards with stats (posts, connections), follow button | `community/network/index.html.twig` | M |
| 4.6 | **Blog** — Featured post hero, reading time, tag pills | `community/blog/index.html.twig` | S |

### Phase 5: Support & Messaging UX (MEDIUM)
_Professional ticket and chat experiences_

| # | Task | Files | Effort |
|---|------|-------|--------|
| 5.1 | **Support show** — Chat-bubble thread layout, internal note styling, timeline sidebar | `support/show.html.twig` | L |
| 5.2 | **Support client list** — Status progress bar per ticket, priority color dots | `support/client/index.html.twig` | M |
| 5.3 | **Support new ticket** — Multi-step wizard (category → details → attachments → preview) | `support/client/new.html.twig` | L |
| 5.4 | **Messaging inbox** — Conversation list with unread badges, active conversation highlight | `messaging/inbox/index.html.twig` | M |

### Phase 6: Recruitment Flow Polish (MEDIUM)
_End-to-end hiring pipeline UX_

| # | Task | Files | Effort |
|---|------|-------|--------|
| 6.1 | **Application show** — Timeline of status changes, score breakdown card, action buttons with modals | `recruitment/applications/show.html.twig` | M |
| 6.2 | **Interview page** — Countdown timer, Jitsi/video embed area, notes panel | `recruitment/interviews/index.html.twig` | M |
| 6.3 | **CV Builder** — Split layout: form left / live preview right | `recruitment/cv/builder.html.twig` | L |
| 6.4 | **Employer job post** — Preview card before submit, rich text description | `recruitment/employer/post_job.html.twig` | M |
| 6.5 | **Hire offers** — Card-based layout with status badges, accept/decline modals | `recruitment/offers/index.html.twig` | M |

### Phase 7: Formation & Learning UX (LOW)
_Already strong, just refinements_

| # | Task | Files | Effort |
|---|------|-------|--------|
| 7.1 | **Formation catalog** — Filter chips, rating stars, enrollment count badge | `formation/catalog/index.html.twig` | M |
| 7.2 | **Learning show** — Video/PDF viewer area, module progress sidebar, completion confetti | `formation/learning/show.html.twig` | L |
| 7.3 | **Quiz take** — Progress bar, timer (if timed), animated question transitions | `formation/quiz/take.html.twig` | M |
| 7.4 | **Certificate show** — Shareable card with QR code, download/print buttons | `formation/certificates/show.html.twig` | S |

### Phase 8: Chatbot & Notification Polish (LOW)
_Final touches_

| # | Task | Files | Effort |
|---|------|-------|--------|
| 8.1 | **Chatbot widget** — Inline slide-up panel instead of iframe, typing indicator, message bubbles | `components/_chatbot_widget.html.twig`, `chatbot/index.html.twig` | M |
| 8.2 | **Notifications** — Group by date, "mark all read" button, empty state illustration, unread dot | `notifications/index.html.twig` | M |
| 8.3 | **User profile** — Stats section (posts, reviews, formations), editable bio, avatar upload preview | `user/profile/index.html.twig` | M |
| 8.4 | **Settings** — Tab-based layout (Account, Security, Preferences, Notifications) | `user/settings/index.html.twig` | M |

---

## Execution Priority

```
Phase 1 (Foundation)     ████████████████████  FIRST — unlocks everything
Phase 2 (Dashboards)     ████████████████████  HIGH  — biggest visual impact
Phase 3 (Admin panels)   ██████████████        MEDIUM
Phase 4 (Community)      ██████████████        MEDIUM
Phase 5 (Support/DM)     ██████████████        MEDIUM
Phase 6 (Recruitment)    ██████████████        MEDIUM
Phase 7 (Formation)      ████████              LOW — already polished
Phase 8 (Chatbot/Notif)  ████████              LOW — finishing touches
```

---

## Design Tokens (reference for all phases)

| Token | Value |
|-------|-------|
| Border radius (buttons, inputs) | `rounded-xl` (12px) |
| Border radius (cards) | `rounded-2xl` (16px) |
| Panel style | `glass-panel` (frosted glass + border) |
| Heading style | `text-4xl font-light tracking-holographic` |
| Label style | `text-xs font-bold uppercase tracking-label text-primary` |
| Stat number | `text-3xl font-light tracking-holographic` |
| Primary actions | `bg-primary text-primary-foreground font-bold` |
| Secondary actions | `border border-border bg-background/70 font-bold` |
| Colors: success | `emerald-500` |
| Colors: warning | `amber-500` |
| Colors: danger | `rose-500` |
| Colors: info | `sky-500` |
| Transition | `transition-colors`, `hover:bg-muted/40` |
| Modal backdrop | Alpine.js `x-show` + `bg-black/50 backdrop-blur-sm` |

---

## Next Step
Begin with **Phase 1** — foundation components that every page will use.
