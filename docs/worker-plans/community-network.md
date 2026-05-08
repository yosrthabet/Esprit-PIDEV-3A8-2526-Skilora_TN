# Community Network Worker Plan

Branch: `feat/community-network`  
Worktree: `/Users/nyxfallagatn/Desktop/WEB_Worktrees/community-network`  
Coordinator branch: `coord/platform-checkpoint`

## Mission

Restore old Community network/social parity while preserving the current clean `App\Community` and `App\Messaging` modules.

Privacy decision from coordinator/user intent:

- Restore old behavior: **gate new DMs by accepted friends/connections**.

Current baseline already includes:

- Community feed/posts/comments/likes/moderation.
- Groups/events/blog.
- Direct inbox/messaging.

## File Ownership

Allowed to edit:

- `src/Community/**`
- `src/Messaging/**`
- `templates/community/**`
- `templates/messaging/**`
- tests related to Community/Messaging

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

- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Community/CommunityNetworkController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Community/ChatApiController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Community/CommunityApiController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Community/CommunityGroupController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Community/CommunityEventController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/src/Controller/Community/CommunityBlogController.php`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/community/network/index.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/community/_subnav.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/community/posts/edit.html.twig`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/community/groups/**`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/community/events/**`
- `/Users/nyxfallagatn/ESPRIT/WEB/templates/community/blog/**`

## Approved Slice A: Network Invitations/Friends, Migration Likely Needed But Existing Legacy Table May Exist

Goal: restore friends/network page.

Research noted `member_invitations` appears in migrations but no current entity exists.

Implement if current DB/table is available or coordinator approves mapping/migration:

- `App\Community\Entity\MemberInvitation`
- `App\Community\Repository\MemberInvitationRepository`
- `CommunityNetworkController`
- `templates/community/network/index.html.twig`

Routes:

- `GET /community/network`
- `POST /community/network/invitations/{userId}/send`
- `POST /community/network/invitations/{id}/accept`
- `POST /community/network/invitations/{id}/decline`
- `POST /community/network/invitations/{id}/cancel`

Statuses:

- pending
- accepted
- declined
- cancelled

Rules:

- no self-invites
- no admin users in normal network suggestions
- no duplicate pending/accepted invite pairs
- CSRF on all mutations

Update:

- `templates/community/_tabs.html.twig` to add Network tab

If schema is missing, stop and report exact migration needs.

## Approved Slice B: Gate New DMs By Friends

Goal: restore old privacy behavior.

Implement after Slice A repository exists.

In `MessagingController`:

- `availableContacts()` returns accepted friends only.
- `start()` rejects non-friends.
- `send()` rejects non-friends unless coordinator decides existing conversations should remain sendable.

Coordinator decision:

- Existing non-friend conversations should remain readable.
- Block starting/sending new messages with non-friends.

Templates:

- `templates/messaging/inbox/index.html.twig`
  - show empty state explaining “connect first” if no friends.

## Approved Slice C: Message Edit/Delete, No Migration For Basic Version

Goal: restore message controls.

Basic version:

- own-message edit updates body directly
- own-message delete hard deletes message

Routes:

- `GET|POST /inbox/messages/{id}/edit`
- `POST /inbox/messages/{id}/delete`

Rules:

- participant required
- sender owns message
- CSRF for POST

Blocked enhanced version:

- `edited_at` / `deleted_at` audit fields require migration.

## Approved Slice D: Group/Event/Blog Owner Controls, No Migration Except Event Cancel

Groups:

- edit group
- delete group
- leave group
- members list
- owner cannot leave own group

Events:

- edit event
- delete event
- true cancel requires status/cancelled_at migration; without migration implement delete only

Blog:

- edit article
- delete article
- archive article using existing `BlogArticleStatus::ARCHIVED`

## Approved Slice E: Search And Mentions API, No Migration

Goal: restore old discovery utilities.

Routes:

- `GET /community/search`
- `GET /community/mentions/autocomplete`

Search should cover:

- posts
- groups
- events
- blog articles
- users for mentions, excluding inactive/admin if appropriate

Return safe JSON only.

## Blocked/Needs Coordinator Approval

- Post image upload requires `community_feed_posts.image_url` or separate attachment table.
- True event cancel requires status/cancelled fields.
- Message edit/delete audit requires `edited_at` and/or `deleted_at`.

Do not implement these schema changes until coordinator approves migrations.

## Tests To Add/Run

Update:

- `tests/Controller/CommunityRouteRegistrationTest.php`
- `tests/Controller/MessagingRouteRegistrationTest.php`
- `tests/Entity/CommunityPostTest.php`
- `tests/Entity/CommunitySpacesTest.php`
- `tests/Entity/MessagingTest.php`

Add:

- network invitation entity/repository tests if practical

Run targeted:

```bash
php bin/phpunit tests/Controller/CommunityRouteRegistrationTest.php tests/Controller/MessagingRouteRegistrationTest.php tests/Entity/CommunityPostTest.php tests/Entity/CommunitySpacesTest.php tests/Entity/MessagingTest.php
php bin/console lint:twig templates/community templates/messaging
vendor/bin/phpstan analyse --no-progress
```

## Worker Report Format

Return:

- Restored features.
- Changed files.
- New routes.
- Needed coordinator migration/nav/config changes.
- Tests run and results.
- Remaining gaps.
