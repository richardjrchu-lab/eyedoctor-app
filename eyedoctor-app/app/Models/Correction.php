<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Correction extends Model
{
    use HasFactory;

    protected $fillable = [
        'prediction_id',
        'corrected_by',
        'corrected_class',
        'note',
    ];

    protected $casts = [
        'note' => 'encrypted',
    ];

    public function prediction()
    {
        return $this->belongsTo(Prediction::class);
    }

    public function correctedBy()
    {
        return $this->belongsTo(User::class, 'corrected_by');
    }
}