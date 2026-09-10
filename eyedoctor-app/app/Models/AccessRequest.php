<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AccessRequest extends Model
{
    public const STATUS_EMAIL_PENDING = 'email_pending';

    public const STATUS_PENDING_REVIEW = 'pending_review';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    /**
     * Allowed lifecycle states for an access request.
     *
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_EMAIL_PENDING,
        self::STATUS_PENDING_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    /**
     * Attributes that may be mass assigned by trusted server-side code.
     *
     * Security-sensitive lifecycle fields such as reviewed_by,
     * approved_user_id, status, and proof deletion timestamps are
     * intentionally excluded and must be changed explicitly.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'profession',
        'institution',
        'department_position',
        'license_registration_number',
        'license_registration_last4',
        'proof_type',
        'proof_disk',
        'proof_object_key',
        'proof_mime_type',
        'proof_size_bytes',
        'proof_sha256',
        'proof_uploaded_at',
        'submission_count',
        'last_submitted_at',
        'email_verification_sent_at',
        'privacy_consent_at',
        'privacy_notice_version',
        'appropriate_use_consent_at',
        'appropriate_use_notice_version',
    ];

    /**
     * Sensitive internal fields should not be exposed accidentally when
     * this model is serialized to arrays or JSON.
     *
     * @var list<string>
     */
    protected $hidden = [
        'license_registration_number',
        'proof_disk',
        'proof_object_key',
        'proof_sha256',
    ];

    /**
     * Attribute casts.
     *
     * The professional registration number is encrypted at rest using
     * Laravel's application encryption key.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'license_registration_number' => 'encrypted',

            'proof_size_bytes' => 'integer',
            'submission_count' => 'integer',

            'proof_uploaded_at' => 'datetime',
            'proof_deleted_at' => 'datetime',

            'last_submitted_at' => 'datetime',

            'email_verification_sent_at' => 'datetime',
            'email_verified_at' => 'datetime',

            'reviewed_at' => 'datetime',
            'account_setup_sent_at' => 'datetime',

            'privacy_consent_at' => 'datetime',
            'appropriate_use_consent_at' => 'datetime',
        ];
    }

    /**
     * Model lifecycle safeguards.
     */
    protected static function booted(): void
    {
        static::creating(function (AccessRequest $request): void {
            if (! $request->public_id) {
                $request->public_id = (string) Str::uuid();
            }
        });

        static::saving(function (AccessRequest $request): void {
            $email = trim((string) $request->email);

            $request->email = $email;
            $request->email_normalized = Str::lower($email);

            /*
             * Apply the lifecycle default at the application layer.
             *
             * Database defaults are not available yet when Eloquent's
             * saving event runs.
             */
            if (! $request->status) {
                $request->status = self::STATUS_EMAIL_PENDING;
            }

            if (! in_array($request->status, self::STATUSES, true)) {
                throw new \InvalidArgumentException(
                    'Invalid access request status.'
                );
            }
        });
    }

    /**
     * Administrator who reviewed the application.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'reviewed_by'
        );
    }

    /**
     * RETINA account created from an approved application.
     */
    public function approvedUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'approved_user_id'
        );
    }

    /**
     * Immutable lifecycle history for this application.
     */
    public function events(): HasMany
    {
        return $this->hasMany(
            AccessRequestEvent::class
        )->orderBy('created_at');
    }

    /**
     * Restrict a query to one application state.
     */
    public function scopeWithStatus(
        Builder $query,
        string $status
    ): Builder {
        return $query->where('status', $status);
    }

    public function isEmailPending(): bool
    {
        return $this->status === self::STATUS_EMAIL_PENDING;
    }

    public function isPendingReview(): bool
    {
        return $this->status === self::STATUS_PENDING_REVIEW;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
}
