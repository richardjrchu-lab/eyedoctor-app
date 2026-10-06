<?php

namespace App\Http\Controllers;

use App\Models\LegalAcceptance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalController extends Controller
{
    public function showEula(Request $request): View
    {
        return view('legal.eula');
    }

    public function acceptEula(Request $request): RedirectResponse
    {
        $request->validate([
            'agree' => ['accepted'],
        ]);

        LegalAcceptance::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'document_type' => 'eula',
                'document_version' => (string) config('legal.eula_version'),
            ],
            [
                'accepted_at' => now(),
            ]
        );

        return redirect()->route('legal.privacy');
    }

    public function showPrivacy(Request $request): View|RedirectResponse
    {
        $eulaAccepted = LegalAcceptance::query()
            ->where('user_id', $request->user()->id)
            ->where('document_type', 'eula')
            ->where(
                'document_version',
                (string) config('legal.eula_version')
            )
            ->exists();

        if (!$eulaAccepted) {
            return redirect()->route('legal.eula');
        }

        return view('legal.privacy');
    }

    public function acceptPrivacy(Request $request): RedirectResponse
    {
        $request->validate([
            'acknowledge' => ['accepted'],
        ]);

        LegalAcceptance::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'document_type' => 'privacy',
                'document_version' => (string) config('legal.privacy_version'),
            ],
            [
                'accepted_at' => now(),
            ]
        );

        return redirect()->intended(
            $this->defaultDestination($request)
        );
    }

    private function defaultDestination(Request $request): string
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            return route('history');
        }

        if ($user->hasRole('doctor')) {
            return route('welcome');
        }

        return route('profile.edit');
    }
}