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
        Schema::create('access_request_events', function (Blueprint $table) {
            $table->id();

            /*
             * Parent access request.
             *
             * If an application must eventually be removed under the
             * project's retention/privacy policy, its event history is
             * removed with it rather than leaving an orphaned record.
             */
            $table->foreignId('access_request_id')
                ->constrained('access_requests')
                ->cascadeOnDelete();

            /*
             * Examples:
             * submitted
             * email_verification_sent
             * email_verified
             * resubmitted
             * approved
             * rejected
             * account_setup_sent
             * proof_deleted
             */
            $table->string('event_type', 64);

            /*
             * Identifies the source of the event without requiring an
             * authenticated user for public/applicant actions.
             *
             * Expected values:
             * applicant
             * admin
             * system
             */
            $table->string('actor_type', 32);

            /*
             * Present when the event was performed by an authenticated
             * administrator. Applicant/system events leave this null.
             */
            $table->foreignId('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Status transition, when the event changes lifecycle state.
             *
             * Non-transition events may leave one or both fields null.
             */
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();

            /*
             * Event records are append-only history and therefore have
             * no updated_at column.
             */
            $table->timestamp('created_at')->useCurrent();

            /*
             * Primary administrative access pattern:
             * timeline for one application ordered by event time.
             */
            $table->index([
                'access_request_id',
                'created_at',
            ]);

            /*
             * Supports aggregate/security review of event categories.
             */
            $table->index([
                'event_type',
                'created_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_request_events');
    }
};
