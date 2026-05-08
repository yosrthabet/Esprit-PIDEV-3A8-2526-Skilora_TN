# Coordinator Integration Plan

Coordinator branch: `coord/platform-checkpoint`  
Coordinator repo: `/Users/nyxfallagatn/Desktop/WEB_Final`

Worker branches:

- `feat/support-parity`
- `feat/recruitment-parity`
- `feat/finance-parity`
- `feat/community-network`

## Integration Rules

Workers must report before implementation if they need:

- migrations
- route config changes
- Doctrine mapping changes
- navigation changes
- composer dependency changes
- global dashboard changes

The coordinator owns:

- `config/routes.yaml`
- `config/packages/doctrine.yaml`
- `src/Twig/AppExtension.php`
- `migrations/*`
- `composer.json`
- `composer.lock`
- global dashboard templates/controllers
- final full verification

## Merge Order Recommendation

Use this order for first integration cycle:

1. Recruitment, because first slice has no migration and primarily restores route/templates/services.
2. Finance, because first slice has no migration and uses current schema.
3. Support Slice A/B/C, because no migration if attachments are postponed.
4. Community Network Slice A only if schema/mapping is clear; otherwise integrate no-migration owner controls/search first.

## Coordinator Commands For Reviewing Worker Branch

From coordinator repo:

```bash
cd /Users/nyxfallagatn/Desktop/WEB_Final
git fetch --all
git diff --stat coord/platform-checkpoint..feat/recruitment-parity
git diff --name-only coord/platform-checkpoint..feat/recruitment-parity
```

Inspect branch changes before merge:

```bash
git diff coord/platform-checkpoint..feat/recruitment-parity -- src/Recruitment templates/recruitment tests
```

Merge one worker at a time:

```bash
git merge --no-ff feat/recruitment-parity
```

Then run targeted checks, then full checks.

## Required Full Verification

After every successful worker merge, run:

```bash
vendor/bin/phpstan analyse --no-progress
php bin/phpunit
php bin/console lint:container
php bin/console lint:twig templates
php bin/console doctrine:schema:validate --skip-sync
npm run build
```

## Migration Policy

Workers should not generate migrations unless explicitly told.

If a worker needs schema:

1. Worker reports entity/table/columns.
2. Coordinator approves names and relationships.
3. Coordinator either:
   - lets worker add entity only, then coordinator generates migration; or
   - tells worker to include entity and repository, no migration.
4. Coordinator generates migration on integration branch.
5. Coordinator cleans unrelated Doctrine drift before applying.

## Branch Hygiene

Workers should commit their own branch when done:

```bash
git status --short
git add <owned files only>
git commit -m "restore <module> parity slice"
```

Workers should not push unless asked.

## Report Required From Each Worker

```text
WORKER REPORT

Branch:
Module:
Status:

Restored features:
- ...

Changed files:
- ...

Shared changes needed from coordinator:
- routes:
- doctrine mapping:
- migration:
- nav:
- dashboard:
- env/config:

Tests run:
- command: result

Known risks:
- ...

Next recommended slice:
- ...
```
