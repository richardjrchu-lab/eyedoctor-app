<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_id',
        'predicted_class',
        'confidence_score',
        'probabilities',
        'referral_flag',
        'gradcam_path',
        'model_version',
    ];

    protected $casts = [
        'probabilities' => 'array',
        'referral_flag' => 'boolean',
    ];

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function correction()
    {
        return $this->hasOne(Correction::class);
    }
}