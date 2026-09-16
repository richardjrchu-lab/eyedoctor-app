# RETINA Phase B3 — One-Year Retinal Image Retention Design

**Date:** September 16, 2026
**Branch:** `phase-b3-image-retention`
**Status:** Design approved; implementation not yet started.

## 1. Objective

RETINA Web will retain each sanitized uploaded retinal image for up to one calendar year from its original upload timestamp.

The policy applies to:

- all existing stored image records;
- all future stored image records;
- successful predictions;
- rejected fundus uploads;
- prediction-error records;
- any other image row whose source file remains in persistent storage.

The retention age is always calculated from the image record's existing `created_at` timestamp. Deploying Phase B3 does not restart or extend the retention period for older records.

After the one-year retention period, RETINA deletes the stored source retinal image while preserving the coded research and prediction record.

## 2. Data preserved after image purge

The following remain after source-image deletion:

- `images.id`
- `images.user_id`
- `images.study_case_id`
- `images.anonymized_filename`
- `images.validation_status`
- image timestamps
- prediction record
- referral result
- class probabilities
- clinician correction
- model version
- other coded research fields

`validation_status` remains unchanged because it describes upload/model processing and is not a retention-state field.

## 3. Database changes

### images

Modify:

- `storage_path` → nullable

Add:

- `retention_purged_at` → nullable timestamp

A purged record will therefore have:

- `storage_path = NULL`
- `retention_purged_at = <successful purge time>`

### retention_purge_logs

Create a dedicated audit table containing, at minimum:

- `id`
- `image_id`
- `mode`
- `outcome`
- `message`
- `attempted_at`
- timestamps

Allowed modes:

- `dry_run`
- `live`

Expected outcomes:

- `eligible`
- `purged`
- `already_missing`
- `failed`

The log must not contain retinal image bytes, credentials, secrets, patient identifiers, or storage access keys.

## 4. Eligibility rule

An image is eligible when all of the following are true:

1. `retention_purged_at IS NULL`
2. `storage_path IS NOT NULL`
3. `created_at <= now - 1 calendar year`

The same rule applies regardless of `validation_status`.

The query must process records in a deterministic order, oldest first, with a capped batch size.

## 5. Purge service

Retention deletion logic will live in one dedicated application service.

The service is the only component allowed to perform the source-image retention purge.

Responsibilities:

1. locate eligible records;
2. enforce the batch limit;
3. support dry-run behavior;
4. verify the current storage state;
5. delete the source object from the configured S3/Supabase disk;
6. update the image row only after the privacy objective has been satisfied;
7. create a retention-purge audit row for each attempt;
8. continue safely when one individual record fails.

The HTTP controller must not contain storage-deletion business logic.

## 6. Storage deletion behavior

### File exists and deletion succeeds

Sequence:

1. delete object from Supabase Storage;
2. confirm deletion operation completed successfully;
3. set `storage_path = NULL`;
4. set `retention_purged_at = now`;
5. log outcome `purged`.

The database must never claim an image is purged before the storage deletion succeeds.

### File is already absent

If the database still contains a storage path but the source object no longer exists:

1. record outcome `already_missing`;
2. set `storage_path = NULL`;
3. set `retention_purged_at = now`.

The privacy goal has already been met, but the discrepancy remains auditable.

### Storage error

If storage access or deletion throws/fails:

1. do not clear `storage_path`;
2. do not set `retention_purged_at`;
3. log outcome `failed`;
4. continue to the next eligible record when safe;
5. allow a later scheduled run to retry.

This is fail-closed behavior.

## 7. Artisan command

Create:

`php artisan retina:purge-expired`

The Artisan command is the authoritative purge entry point.

It must support:

- `--dry-run`
- `--limit=50`

The live command must never accept an unlimited batch.

Dry-run behavior:

- query using the real production eligibility rules;
- perform no storage deletion;
- make no image-state mutation;
- record or report which records are eligible;
- produce clear command output.

Live behavior:

- default maximum batch: 50;
- process oldest eligible records first;
- call the dedicated retention service;
- return a nonzero exit code only for command-level failure, not merely because one record failed.

The command must be safe to rerun.

## 8. Internal trigger endpoint

GitHub Actions cannot directly execute Artisan inside the Render web container.

Therefore RETINA will expose one internal POST endpoint whose only purpose is to trigger the Artisan retention command.

Requirements:

- POST only;
- no browser/session authentication;
- authenticate using a dedicated high-entropy retention secret;
- secret supplied in a dedicated request header;
- compare using constant-time secret comparison;
- reject missing/incorrect secrets;
- rate-limit the endpoint;
- production-only behavior;
- do not echo the secret;
- do not write the secret to logs;
- do not accept arbitrary command names or shell arguments;
- endpoint may invoke only the fixed RETINA retention command with the fixed production batch limit.

The controller remains intentionally thin:

authenticated request
→ invoke fixed Artisan command
→ return compact status

Deletion logic remains exclusively inside the retention service.

## 9. Secret management

Use one dedicated retention-trigger secret.

The value must exist only in:

- Render environment variables;
- GitHub Actions repository secrets.

The secret must never be:

- committed to Git;
- placed in `.env.example` as a real value;
- pasted into source code;
- shown in application logs;
- sent in a URL query parameter.

Only the environment-variable name may appear in source control.

## 10. GitHub Actions scheduler

Create a scheduled workflow that runs once daily.

Flow:

GitHub Actions
→ POST production internal retention endpoint
→ Render web service wakes if asleep
→ authenticated endpoint
→ `retina:purge-expired --limit=50`
→ retention service
→ Supabase Storage deletion
→ DB state update
→ purge audit log

The workflow will also support manual dispatch for controlled verification.

The scheduled workflow must fail visibly when the HTTP trigger returns an unexpected status.

GitHub Actions scheduling is treated as operational automation, not the only evidence of retention compliance. The application-level purge audit provides the research/audit trail.

## 11. UI behavior after purge

### Prediction Detail

If `storage_path` is present and `retention_purged_at` is null:

- render the existing protected image route normally.

If the image has been purged:

- do not emit an `<img>` request;
- display a neutral message such as:

  `Source retinal image removed under the one-year retention policy.`

- show purge date when available;
- continue displaying prediction, referral result, probabilities, Study Case ID, correction, model metadata, and upload date.

### History

History records remain visible after purge.

For purged records:

- preserve normal prediction/referral information;
- optionally display a small `Source image expired` / `Image removed` indicator;
- never treat retention purge as a prediction failure.

### Protected image route

The image endpoint must explicitly handle nullable `storage_path`.

If the source has been purged, it returns a controlled 404 and never calls the storage disk with a null path.

Authorization behavior remains unchanged.

## 12. Existing and future data

No existing image row is deleted by the B3 migration.

No existing prediction or correction is deleted.

Existing records immediately become subject to the same retention calculation using their original `created_at`.

At deployment time in September 2026, normal recent records should not be eligible yet.

Testing must therefore use controlled timestamps rather than waiting one year.

## 13. Foreign-key scope

Phase B3 will not change the existing image/prediction/user cascade relationships.

Reason:

- `images.user_id` currently cascades from users;
- `predictions.image_id` currently cascades from images;
- corrections also have user/prediction relationships.

Changing only one relationship could break account deletion or create inconsistent lifecycle behavior.

Foreign-key lifecycle hardening will be handled as a separate coordinated phase after B3.

B3 itself never deletes an Image database row, so the existing prediction cascade is not exercised by the retention purge.

## 14. Testing requirements

Automated tests must cover at least:

1. image younger than one year is not eligible;
2. image exactly at/older than one year is eligible;
3. valid prediction image is eligible;
4. rejected image is eligible;
5. error image is eligible;
6. dry run does not delete storage;
7. dry run does not modify image row;
8. live purge deletes storage object;
9. successful purge nulls `storage_path`;
10. successful purge sets `retention_purged_at`;
11. prediction survives purge;
12. clinician correction survives purge;
13. Study Case ID survives purge;
14. already-missing object is marked purged and audited;
15. storage failure leaves DB state unchanged;
16. storage failure records a failed audit outcome;
17. batch cap is enforced;
18. oldest records are processed first;
19. rerunning the command does not repurge completed records;
20. expired detail page does not generate an image request;
21. protected image route returns controlled 404 for a purged image;
22. unauthorized trigger request is rejected;
23. authorized trigger invokes only the fixed purge command;
24. secret is not exposed in the response.

Existing RETINA regression tests must continue to pass.

## 15. Production rollout

Deployment sequence:

1. merge only after full automated regression passes;
2. Render runs database migrations through the existing deployment process;
3. verify new columns/table exist;
4. configure the retention-trigger secret in Render;
5. configure the same value in GitHub Actions secrets;
6. deploy the workflow;
7. run production endpoint in a no-destructive verification state where possible;
8. verify no current 2026 records are unexpectedly eligible;
9. verify History and Prediction Detail still work;
10. verify normal image retrieval still works;
11. record the B3 production checkpoint.

No production record will be artificially backdated solely to test destructive deletion.

Destructive purge behavior will be covered by automated tests and safe controlled non-production data.

## 16. Success criteria

Phase B3 is complete only when:

- one-year retention is encoded from original `created_at`;
- all stored image statuses are covered;
- physical source-image deletion is implemented;
- prediction and correction records survive;
- purge actions are auditable;
- dry-run and batch limit protections work;
- missing storage files reconcile safely;
- storage failures fail closed;
- expired-image UI works;
- internal trigger is secret-protected;
- daily GitHub Actions scheduling is configured;
- full test suite passes;
- production migration/deployment is verified;
- existing recent production images remain available;
- no existing research record is deleted during rollout.
