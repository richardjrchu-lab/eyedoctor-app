<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRequestEvent extends Model
{
    public const UPDATED_AT = null;

    public const ACTOR_APPLICANT = 'applicant';

    public const ACTOR_ADMIN = 'admin';

    public const ACTOR_SYSTEM = 'system';

    /**
     * Supported event sources.
     *
     * @var list<string>
     */
    public const ACTOR_TYPES = [
        self::ACTOR_APPLICANT,
        self::ACTOR_ADMIN,
        self::ACTOR_SYSTEM,
    ];

    /**
     * Supported lifecycle event categories.
     *
     * @var list<string>
     */
    public const EVENT_TYPES = [
        'submitted',
        'email_verification_sent',
        'email_verified',
        'resubmitted',
        'approved',
        'rejected',
        'account_setup_sent',
        'proof_deleted',
    ];

    /**
     * Only trusted server-side code creates event records.
     *
     * @var list<string>
     */
    protected $fillable = [
        'access_request_id',
        'event_type',
        'actor_type',
        'actor_user_id',
        'from_status',
        'to_status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (AccessRequestEvent $event): void {
            if (! in_array(
                $event->event_type,
                self::EVENT_TYPES,
                true
            )) {
                throw new \InvalidArgumentException(
                    'Invalid access request event type.'
                );
            }

            if (! in_array(
                $event->actor_type,
                self::ACTOR_TYPES,
                true
            )) {
                throw new \InvalidArgumentException(
                    'Invalid access request event actor type.'
                );
            }
        });

        /*
         * Audit-history records are immutable after creation.
         *
         * Parent-request removal may still cascade at the database level
         * under the project's retention/privacy policy.
         */
        static::updating(function (): void {
            throw new \LogicException(
                'Access request events cannot be modified.'
            );
        });

        static::deleting(function (): void {
            throw new \LogicException(
                'Access request events cannot be deleted directly.'
            );
        });
    }

    /**
     * Application associated with this historical event.
     */
    public function accessRequest(): BelongsTo
    {
        return $this->belongsTo(AccessRequest::class);
    }

    /**
     * Authenticated administrator responsible for the event, when present.
     */
    public function actorUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actor_user_id'
        );
    }
}
