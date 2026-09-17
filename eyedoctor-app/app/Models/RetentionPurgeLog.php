<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetentionPurgeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_id',
        'mode',
        'outcome',
        'message',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }
}
