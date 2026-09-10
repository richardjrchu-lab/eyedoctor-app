<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();

            /*
             * Public identifier.
             *
             * Internal numeric IDs are never required to be exposed
             * to unauthenticated applicants.
             */
            $table->uuid('public_id')->unique();

            /*
             * Applicant identity and professional information.
             */
            $table->string('full_name');
            $table->string('email');
            $table->string('email_normalized')->unique();

            $table->string('profession');
            $table->string('institution');
            $table->string('department_position')->nullable();

            /*
             * Professional registration information.
             *
             * The full value will be encrypted by the Eloquent model.
             * A separate last-four field allows masked display in the
             * administrator request list without decrypting every value.
             */
            $table->text('license_registration_number')->nullable();
            $table->string('license_registration_last4', 4)->nullable();

            /*
             * Verification document metadata.
             *
             * The file itself lives in dedicated private object storage.
             * No public URL is stored in the database.
             */
            $table->string('proof_type');
            $table->string('proof_disk')
                ->default('professional_verifications');

            $table->string('proof_object_key')->unique();
            $table->string('proof_mime_type', 100);
            $table->unsignedBigInteger('proof_size_bytes');
            $table->char('proof_sha256', 64);

            $table->timestamp('proof_uploaded_at');
            $table->timestamp('proof_deleted_at')->nullable();

            /*
             * Application lifecycle.
             *
             * Expected states:
             * email_pending
             * pending_review
             * approved
             * rejected
             */
            $table->string('status')
                ->default('email_pending');

            $table->unsignedInteger('submission_count')
                ->default(1);

            $table->timestamp('last_submitted_at');

            /*
             * Applicant email verification.
             *
             * Verification links will use Laravel temporary signed URLs,
             * so plaintext verification tokens are not stored here.
             */
            $table->timestamp('email_verification_sent_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();

            /*
             * Administrative review.
             */
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            /*
             * Created RETINA account after approval.
             *
             * One approved access request may create at most one user.
             */
            $table->foreignId('approved_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->unique('approved_user_id');

            /*
             * Tracks delivery of the secure post-approval account
             * setup invitation independently from the review decision.
             */
            $table->timestamp('account_setup_sent_at')->nullable();

            /*
             * Consent records.
             *
             * These record when, and under which notice version,
             * the applicant agreed to the required notices.
             */
            $table->timestamp('privacy_consent_at');
            $table->string('privacy_notice_version');

            $table->timestamp('appropriate_use_consent_at');
            $table->string('appropriate_use_notice_version');

            $table->timestamps();

            /*
             * Common administrative queries.
             */
            $table->index([
                'status',
                'created_at',
            ]);

            $table->index('email_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
