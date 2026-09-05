<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'target_id',
        'target_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Convenience helper for logging an action anywhere in the app.
    public static function record(string $action, ?int $targetId = null, ?string $targetType = null)
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target_id' => $targetId,
            'target_type' => $targetType,
        ]);
    }
}