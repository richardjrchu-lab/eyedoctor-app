<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalAcceptance extends Model
{
    protected $fillable = [
        'user_id',
        'document_type',
        'document_version',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
        ];
    }
}