# Support Parity Worker Plan

Branch: `feat/support-parity`  
Worktree: `/Users/nyxfallagatn/Desktop/WEB_Worktrees/support-parity`  
Coordinator branch: `coord/platform-checkpoint`

## Mission

Restore high-value Support module parity from `/Users/nyxfallagatn/ESPRIT/WEB` into the clean current `App\Support` module without copying legacy code blindly.

Current Support baseline already has:

- User ticket list/new/show.
- Admin ticket list/show/status update.
- Polling ticket chat.
- Internal admin notes.
- Basic notifications.

This worker should first restore features that do not need schema changes, then report schema needs for attachments/feedback before implementation.

## File Ownership

Allowed to edit:

- `src/Support/**`
- `templates/support/**`
- `tests/**/*Support*.php`
- `tests/Controller/SupportRouteRegistrationTest.php` if created
- Support-specific test fixtures/helpers if needed

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

- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Support/SupportClientController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Admin/SupportTicketAdminController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Form/TicketType.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Form/MessageTicketType.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/support/client/index.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/support/client/_dialog_form.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/support/client/_ticket_grid.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/support/admin/index.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/support/admin/_ticket_table.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/support/client/pdf_export.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/support/admin/pdf_export.html.twig`

## Approved Slice A: Search And Filters, No Migration

Goal: restore ticket list usability.

Implement:

- User ticket list filters:
  - search query on subject/message-like fields available in current schema
  - status filter
  - category filter
  - priority filter
- Admin ticket list filters:
  - search query
  - status filter
  - category filter
  - priority filter
  - sorting by newest/updated/priority if simple
- Repository helper methods on current Support repositories.
- Preserve current ticket/chat detail behavior.

Likely files:

- `src/Support/Repository/SupportTicketRepository.php`
- `src/Support/Controller/SupportController.php`
- `templates/support/client/index.html.twig`
- `templates/support/admin/index.html.twig`

Acceptance criteria:

- Existing `/support` still loads.
- Existing `/admin/support` still loads.
- Empty states remain useful.
- Filters persist in query string.
- No schema changes.

## Approved Slice B: Admin Metrics And CSV Export, No Migration

Goal: recover operational admin overview.

Implement:

- Admin support stats cards:
  - open ticket count
  - pending/in-progress/resolved/closed counts, based on current status enum
  - priority distribution
  - category distribution
  - tickets created in last 7 days
- CSV export route for admin ticket list.

Suggested routes:

- `GET /admin/support/export.csv`
- Route name: `app_admin_support_export_csv`

Allowed because controller is already imported through `src/Support/Controller`.

Acceptance criteria:

- Admin-only route.
- CSV has safe headers and no raw internal notes unless intentionally included.
- Search/filter parameters can optionally affect export.

## Approved Slice C: PDF Export, No Migration

Goal: restore ticket PDF export if Dompdf is available.

Implement:

- User PDF export for own ticket.
- Admin PDF export for any ticket.
- Graceful HTML fallback if `Dompdf\Dompdf` class is unavailable.

Suggested routes:

- `GET /support/{id}/export.pdf`
- Route name: `app_support_export_pdf`
- `GET /admin/support/{id}/export.pdf`
- Route name: `app_admin_support_export_pdf`

Likely templates:

- `templates/support/pdf/ticket.html.twig`

Acceptance criteria:

- Access checks match ticket ownership/admin.
- Does not expose internal notes to requester PDF unless intended.
- Admin PDF may include internal notes if clearly labeled.

## Schema Slice D: Attachments, Requires Coordinator Approval

Do not implement until coordinator approves migration.

Proposed entity/table:

- Entity: `App\Support\Entity\SupportAttachment`
- Table: `support_attachments`

Fields:

- `id`
- `ticket_id` FK `support_tickets.id` cascade
- `message_id` nullable FK `support_messages.id` cascade or set null
- `uploaded_by_id` FK `users.id`
- `original_name`
- `stored_name`
- `mime_type`
- `size_bytes`
- `relative_path`
- `created_at`

Upload validation:

- max size: propose 5 MB
- allowed MIME: PDF, images, plain text, Word documents if desired
- store under `var/uploads/support/YYYY/MM`
- never serve files without access checks

Coordinator must handle:

- migration generation/apply
- any service binding for upload dir if needed

## Schema Slice E: Feedback, Requires Coordinator Approval

Do not implement until coordinator approves.

Proposed entity/table:

- Entity: `App\Support\Entity\SupportFeedback`
- Table: `support_feedback`

Fields:

- `id`
- `ticket_id` FK support ticket
- `user_id` FK requester
- `rating` 1-5
- `comment` nullable text
- `created_at`
- unique `(ticket_id, user_id)`

## Do Not Restore In First Pass

- AI subject suggestion.
- AI text correction.
- Translation.
- Sentiment analysis.
- ML triage/smart replies.
- Calendar integration.

Reason: current clean app does not have the old Gemini/ML/Translation services wired. These require a separate product decision and service integration.

## Security Rules

- Every mutating route must validate CSRF.
- User routes must assert requester owns the ticket.
- Admin routes require `ROLE_ADMIN`.
- Never expose local file paths.
- Escape all user-generated content in Twig.
- Do not use raw `innerHTML` for message rendering.

## Tests To Add/Run

Add if missing:

- `tests/Controller/SupportRouteRegistrationTest.php`
- repository/service tests for filters if practical
- attachment validation tests only if attachments are implemented

Run targeted:

```bash
php bin/phpunit tests/Controller/*Support* tests/Entity/*Support* tests/Service/*Support*
php bin/console lint:twig templates/support
vendor/bin/phpstan analyse --no-progress
```

## Worker Report Format

Return:

- Features restored.
- Changed files.
- New routes.
- Needed coordinator migration/config/nav changes.
- Tests run and results.
- Remaining gaps.
