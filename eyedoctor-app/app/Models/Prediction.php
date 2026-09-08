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
        'referable_probability',
'flagged_for_review',
        'atypical_fundus_image',
        'fundus_signature_score',
        'gradcam_path',
        'model_version',
    ];

    protected $casts = [
        'probabilities' => 'array',
        'referral_flag' => 'boolean',
 'flagged_for_review' => 'boolean',
        'atypical_fundus_image' => 'boolean',
        'fundus_signature_score' => 'float',
        'confidence_score' => 'float',
        'referable_probability' => 'float',
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