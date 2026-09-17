# RETINA Phase B3 Image Retention Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement auditable one-year source-retinal-image retention for all existing and future RETINA Web image records while preserving coded image rows, predictions, clinician corrections, and research metadata.

**Architecture:** A dedicated `ImageRetentionService` owns retention eligibility, storage deletion, database state changes, and per-image audit logging. A fixed Artisan command is the authoritative execution path. A secret-protected internal POST endpoint may invoke only that command. Supabase Cron + `pg_net` calls the endpoint once daily using a secret stored in Supabase Vault.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent, Pest 3, Flysystem AWS S3 v3, Supabase S3-compatible Storage, Supabase Postgres, `pg_cron`, `pg_net`, Supabase Vault, Render.

**Spec:** `docs/superpowers/specs/2026-09-16-retina-image-retention-design.md`

## Global constraints
- Retention is one calendar year from original `created_at`, using `addYearNoOverflow()`.
- Applies to all existing/future stored images, regardless of `validation_status`.
- Never delete the `images` DB row during retention purge.
- Preserve Prediction, Correction, Study Case ID, anonymized filename, validation status, timestamps, probabilities, referral result, model version, and coded research data.
- `validation_status` is never reused as retention state.
- Live batches are capped at 50.
- Delete the Supabase Storage object before marking the DB row purged.
- Missing object => reconcile and audit `already_missing`.
- Storage failure => leave DB state unchanged and audit `failed`.
- Never log image bytes, credentials, patient identifiers, access keys, trigger secrets, or raw storage exception messages.
- Existing cascade relationships remain unchanged in B3.
- No production record may be artificially backdated to test deletion.
- Use TDD for every task and run the full regression suite before merge.

---

## Task 1 — Retention schema and models
**Create**
- `eyedoctor-app/database/migrations/2026_09_17_100000_add_retention_fields_to_images_table.php`
- `eyedoctor-app/database/migrations/2026_09_17_100100_create_retention_purge_logs_table.php`
- `eyedoctor-app/app/Models/RetentionPurgeLog.php`
- `eyedoctor-app/tests/Feature/ImageRetentionSchemaTest.php`

**Modify**
- `eyedoctor-app/app/Models/Image.php`

**Required changes**
- `images.storage_path` becomes nullable.
- Add nullable timestamp `images.retention_purged_at`.
- Create `retention_purge_logs` with `id`, `image_id`, `mode`, `outcome`, `message`, `attempted_at`, timestamps.
- Do not add a foreign key on `retention_purge_logs.image_id`.
- Add casts for retention timestamps.

**TDD cycle**
1. Write `ImageRetentionSchemaTest`.
2. Run `php artisan test tests/Feature/ImageRetentionSchemaTest.php` and confirm RED.
3. Add migrations/model changes.
4. Re-run and confirm GREEN.
5. Run Pint + `git diff --check`.
6. Commit: `Add image retention schema and audit model`.

---

## Task 2 — ImageRetentionService
**Create**
- `eyedoctor-app/app/Services/ImageRetentionService.php`
- `eyedoctor-app/tests/Feature/ImageRetentionServiceTest.php`

**Interface**
```php
public function purgeExpired(bool $dryRun = false, int $limit = 50): array
```

Return:
```php
[
    'eligible' => int,
    'purged' => int,
    'already_missing' => int,
    'failed' => int,
    'dry_run' => bool,
]
```

**Rules**
- limit 1..50 only.
- query where `retention_purged_at IS NULL` and `storage_path IS NOT NULL`.
- order by `created_at`, then `id`.
- expiry uses `$image->created_at->copy()->addYearNoOverflow()->lte($now)`.
- dry-run logs `eligible`, never deletes or mutates.
- live existing object: delete storage first, then DB transaction clears `storage_path`, sets `retention_purged_at`, audits `purged`.
- missing object: DB transaction reconciles and audits `already_missing`.
- exception: DB row unchanged, audit `failed`, safe message only such as `Storage purge failed: RuntimeException`.
- continue safely to later eligible rows.
- preserve Study Case ID, Prediction, Correction.
- rerun is idempotent.

**Tests**
- younger than one year untouched;
- exact-year expiry;
- leap-day behavior;
- valid/rejected/error statuses;
- dry-run non-destructive;
- successful deletion;
- missing object;
- storage exception;
- preservation of Prediction/Correction/Study Case ID;
- oldest-first;
- cap 50;
- invalid limits;
- idempotence.

**TDD cycle**
1. Write failing tests.
2. Confirm RED.
3. Implement minimal service.
4. Confirm GREEN.
5. Format/check.
6. Commit: `Implement fail-closed retinal image retention purge`.

---

## Task 3 — Artisan command
**Create**
- `eyedoctor-app/app/Console/Commands/PurgeExpiredRetinalImages.php`
- `eyedoctor-app/tests/Feature/PurgeExpiredRetinalImagesCommandTest.php`

Command:
```text
retina:purge-expired
```

Options:
```text
--dry-run
--limit=50
```

Requirements:
- reject limit outside 1..50;
- invoke `ImageRetentionService` exactly once;
- print eligible/purged/already_missing/failed summary;
- individual record failures do not cause command failure;
- command-level exceptions do.

Verify:
```powershell
php artisan test tests/Feature/PurgeExpiredRetinalImagesCommandTest.php
php artisan list | Select-String "retina:purge-expired"
```

Commit: `Add RETINA image retention Artisan command`.

---

## Task 4 — Purged-image UI and protected file route
**Modify**
- `eyedoctor-app/app/Http/Controllers/PredictionController.php`
- `eyedoctor-app/resources/views/prediction-detail.blade.php`
- `eyedoctor-app/resources/views/history.blade.php`

**Create**
- `eyedoctor-app/tests/Feature/RetentionPurgedImageUiTest.php`

Protected image route after authorization:
```php
abort_if(
    $image->storage_path === null || $image->retention_purged_at !== null,
    404
);
```

Prediction Detail:
- no `<img>` request for purged source;
- show `Source retinal image removed under the one-year retention policy.`;
- show purge date when available;
- keep all result/research metadata visible.

History:
- keep record;
- show neutral `Source image removed`;
- do not treat retention purge as prediction failure.

Tests cover 200 Detail, no image URL, retained prediction/Study Case ID/correction, 404 file route, and History indicator.

Run:
```powershell
php artisan test tests/Feature/RetentionPurgedImageUiTest.php
php artisan test tests/Feature/StudyCaseIdTest.php
```

Commit: `Handle retention-purged images safely in the UI`.

---

## Task 5 — Secret-protected internal trigger
**Create**
- `eyedoctor-app/config/retention.php`
- `eyedoctor-app/app/Http/Controllers/Internal/RetentionPurgeController.php`
- `eyedoctor-app/tests/Feature/RetentionPurgeEndpointTest.php`

**Modify**
- `eyedoctor-app/routes/web.php`
- `eyedoctor-app/bootstrap/app.php`
- `eyedoctor-app/.env.example`

Config:
```php
return [
    'years' => 1,
    'batch_limit' => 50,
    'trigger_header' => 'X-Retina-Retention-Secret',
    'trigger_secret' => env('RETENTION_TRIGGER_SECRET'),
];
```

`.env.example`:
```dotenv
RETENTION_TRIGGER_SECRET=
```

Route:
```text
POST /internal/retention/purge
```

Requirements:
- allowed only in `production` and `testing`; otherwise 404;
- configured secret minimum 32 characters or generic 503;
- compare with `hash_equals`;
- wrong/missing request secret => generic 401;
- optional boolean `dry_run`;
- invoke fixed command only with fixed cap 50;
- never return Artisan output;
- never accept caller command name or arbitrary limit;
- rate limit `throttle:5,1`;
- CSRF exclude only `internal/retention/purge`.

Verify:
```powershell
php artisan test tests/Feature/RetentionPurgeEndpointTest.php
php artisan route:list --path=internal/retention
```

Commit: `Add protected retention purge trigger`.

---

## Task 6 — Full regression
Run B3 tests:
```powershell
php artisan test `
  tests/Feature/ImageRetentionSchemaTest.php `
  tests/Feature/ImageRetentionServiceTest.php `
  tests/Feature/PurgeExpiredRetinalImagesCommandTest.php `
  tests/Feature/RetentionPurgeEndpointTest.php `
  tests/Feature/RetentionPurgedImageUiTest.php
```

Run B1/B2 regression:
```powershell
php artisan test `
  tests/Unit/ImageSanitizerTest.php `
  tests/Feature/PredictionImageSanitizationTest.php `
  tests/Feature/StudyCaseIdTest.php
```

Run full suite:
```powershell
php artisan test
```

Static verification:
```powershell
vendor\bin\pint --test
git diff --check
git status --short
php artisan route:list --path=internal/retention
php artisan list | Select-String "retina:purge-expired"
```

Local dry-run:
```powershell
php artisan retina:purge-expired --dry-run --limit=50
```

---

## Task 7 — Push, PR, review, merge
Push:
```powershell
git push -u origin phase-b3-image-retention
```

PR title:
```text
Phase B3: add one-year retinal image retention
```

PR must state:
- source files only are purged;
- DB image/prediction/correction records preserved;
- all validation statuses covered;
- fail-closed storage handling;
- audit table added;
- protected internal trigger added;
- production scheduler is Supabase Cron;
- no production record artificially backdated.

Do not merge if migrations delete rows, endpoint exposes secret/arbitrary command/limit, UI still requests purged files, or tests fail.

---

## Task 8 — Production rollout + Supabase Cron
1. Render deploys merged master and runs migrations.
2. Verify nullable `storage_path`, `retention_purged_at`, and `retention_purge_logs`.
3. Generate strong secret locally; never paste it into chat.
4. Add to Render as `RETENTION_TRIGGER_SECRET`.
5. Enable/confirm Supabase `pg_cron` and `pg_net`.
6. Store same secret in Supabase Vault as e.g. `retina_retention_trigger_secret`.
7. Manually invoke production endpoint with `{"dry_run":true}` and secret header.
8. Confirm current 2026 records are not unexpectedly eligible.
9. Verify History, Prediction Detail, and recent source-image retrieval.
10. Create once-daily Supabase Cron job using `pg_net`.
11. Cron SQL retrieves secret from Vault; never embeds literal secret.
12. Cron only calls Laravel endpoint; it never directly mutates image rows or Storage.
13. Verify Cron run history and endpoint success.
14. Record B3 production checkpoint: merge SHA, Render deployment, migrations, dry-run result, eligible count, recent image accessibility, Cron job/run history, and confirmation no production record was artificially aged/deleted.

**B3 is complete only after this production checkpoint passes.**
