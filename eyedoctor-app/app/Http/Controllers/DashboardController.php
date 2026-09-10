<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Prediction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Doctor Dashboard Statistics
        |--------------------------------------------------------------------------
        |
        | The dashboard is doctor-only, so every statistic is explicitly scoped
        | to the currently authenticated doctor's own retinal image records.
        |
        */

        $doctorImages = Image::query()
            ->where('user_id', $user->id);


        $totalUploads = (clone $doctorImages)
            ->count();


        $completedScreenings = (clone $doctorImages)
            ->whereHas('prediction')
            ->count();


        $rejectedUploads = (clone $doctorImages)
            ->where('validation_status', 'rejected_not_fundus')
            ->count();


        $referralCount = Prediction::query()
            ->whereHas('image', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('referral_flag', true)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Recent Screening Activity
        |--------------------------------------------------------------------------
        */

        $recentImages = Image::with([
                'prediction.correction',
            ])
            ->where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get();


        return view('dashboard-overview', [
            'totalUploads' => $totalUploads,
            'completedScreenings' => $completedScreenings,
            'rejectedUploads' => $rejectedUploads,
            'referralCount' => $referralCount,
            'recentImages' => $recentImages,
        ]);
    }
}