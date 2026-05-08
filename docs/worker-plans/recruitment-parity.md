# Recruitment Parity Worker Plan

Branch: `feat/recruitment-parity`  
Worktree: `/Users/nyxfallagatn/Desktop/WEB_Worktrees/recruitment-parity`  
Coordinator branch: `coord/platform-checkpoint`

## Mission

Restore high-value Recruitment parity from `/Users/nyxfallagatn/ESPRIT/WEB` while preserving the current clean `App\Recruitment` module.

Current baseline already includes:

- Job discovery `/jobs`.
- Job detail.
- Dedicated CV-upload application page.
- Private CV serving.
- Job preferences basic UI.
- Employer offers/application pipeline/interviews/hire offers.
- Finance contract bridge from accepted hire offer.

## File Ownership

Allowed to edit:

- `src/Recruitment/**`
- `templates/recruitment/**`
- `tests/**/*Recruitment*.php`
- `tests/Controller/RecruitmentRouteRegistrationTest.php`

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

- `/Users/nyxfallagatn/ESPRIT/WEB/src/Recruitment/Controller/CandidateCvBuilderController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Recruitment/CvBuilder/CvBuilderData.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Recruitment/Form/CvBuilderType.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Recruitment/Form/CvEducationEntryType.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Recruitment/Form/CvExperienceEntryType.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Recruitment/Service/CvPdfGeneratorService.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/recruitment/cv/builder.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/recruitment/cv/pdf/classic.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/recruitment/cv/pdf/modern.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Recruitment/Controller/EmployerApplicationsController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/recruitment/employer/applications/profile.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/recruitment/employer/applications/cover_letter.html.twig`

## Approved Slice A: CV Builder And PDF, No Migration

Goal: restore old candidate CV builder workflow.

Implement current equivalents:

- `src/Recruitment/CvBuilder/CvBuilderData.php`
- `src/Recruitment/Service/CvPdfGeneratorService.php`
- controller route in current `RecruitmentController` or a new `CandidateCvBuilderController` under `src/Recruitment/Controller`
- `templates/recruitment/cv/builder.html.twig`
- `templates/recruitment/cv/pdf/classic.html.twig`
- `templates/recruitment/cv/pdf/modern.html.twig`

Suggested route:

- `GET|POST /mon-espace/cv/generateur`
- Route name: `app_candidate_cv_builder`

Behavior:

- Render builder form.
- Generate PDF using Dompdf if available.
- Store generated PDF under private path, preferably `var/uploads/cvs/generated/YYYY/MM`.
- Save relative path in session key: `candidate_generated_cv_relpath`.
- Do not enable remote Dompdf resources.

Validation:

- max photo upload 2 MB if photo is supported.
- image MIME only for photo.
- required full name/title/email or sensible minimal fields.

## Approved Slice B: Generated CV Reuse In Applications, No Migration

Goal: allow candidates to apply using generated CV instead of uploading.

Implement:

- Add `ApplicationSubmissionService::submitUsingExistingCvPath()`.
- Ensure path is normalized and remains under the configured CV upload directory.
- Check `is_file()` and `is_readable()`.
- Update `templates/recruitment/jobs/apply.html.twig`:
  - If session key `candidate_generated_cv_relpath` exists, show “Use generated CV”.
  - Keep direct CV upload path.

Acceptance criteria:

- Existing CV upload still works.
- Generated CV path cannot escape upload dir.
- Candidate cannot apply without upload or generated CV.

## Approved Slice C: Employer Candidate Profile And Cover Letter Views

Goal: restore old employer review affordances.

Suggested routes:

- `GET /applications/{id}/profile`
- Route name: `app_application_profile`
- Old alias: `GET /employer/candidatures/{id}/profil`
- `GET /employer/candidatures/{id}/lettre`
- Route name: `app_employer_application_cover_letter`
- CV alias: `GET /employer/candidatures/{id}/cv`
- Route name: `app_employer_applications_cv`

Rules:

- Employer-only profile/cover-letter views.
- Use `ApplicationPipelineService::assertEmployerManages()`.
- Show candidate identity, public profile link if available, CV buttons, cover letter, match score, application status, job info.

Templates:

- `templates/recruitment/employer/profile.html.twig`
- `templates/recruitment/employer/cover_letter.html.twig` if separate page is worthwhile

## Approved Slice D: Route Compatibility Aliases, No Migration

Add attributes to existing actions or new redirect actions, without editing `config/routes.yaml`.

Aliases to restore:

- `/offres` -> jobs index, old route name `app_candidate_offres_index`
- `/offres/{id}` -> job show, old route name `app_candidate_offres_show`
- `/mon-espace/candidatures` -> applications, old route name `app_candidate_area_applications`
- `/employer/job-offers` -> active offers, old route name `app_employer_job_offer_index`
- `/employer/job-offers/new` -> post job, old route name `app_employer_job_offer_new`
- `/employer/job-offers/{id}` -> employer offer show, old route name `app_employer_job_offer_show`
- `/employer/job-offers/{id}/edit` -> employer offer edit, old route name `app_employer_job_offer_edit`
- `/employer/job-offers/{id}/close` -> close, old route name if known from old app
- `/employer/job-offers/{id}/delete` -> delete, old route name if known from old app

Acceptance criteria:

- Old browser URLs do not 404.
- Mutating old aliases still require CSRF.

## Approved Slice E: Lightweight Screening Indicators, No Migration

Goal: restore some employer screening value without old SQL/text extraction complexity.

Implement a small service, for example:

- `src/Recruitment/Service/ApplicationQualityScreeningService.php`

Inputs:

- current `Application`
- match score
- cover letter length
- CV attached
- suspiciously short text

Output:

- score
- badges/reasons

Use in:

- employer application list
- employer application profile/show page

Do not port old SQL gateway/patcher.

## Blocked/Needs Coordinator Approval

- Expanding `JobPreference` fields like max salary, preferred currency, remote OK: needs migration.
- WhatsApp/Twilio interview dispatch: needs secrets/dependencies/product approval.
- ML endpoints: needs current ML client/service/product approval.

## Tests To Add/Run

Update:

- `tests/Controller/RecruitmentRouteRegistrationTest.php`

Add if useful:

- CV builder data test.
- CV PDF service smoke test if not brittle.
- Application submission generated-CV path validation test.

Run targeted:

```bash
php bin/phpunit tests/Controller/RecruitmentRouteRegistrationTest.php tests/Entity/RecruitmentApplicationTest.php tests/Entity/RecruitmentJobOfferTest.php
php bin/console lint:twig templates/recruitment
vendor/bin/phpstan analyse --no-progress
```

## Worker Report Format

Return:

- Restored features.
- Changed files.
- New route aliases.
- Needed coordinator migration/nav/config changes.
- Tests run and results.
- Remaining gaps.
