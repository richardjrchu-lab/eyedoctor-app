<?php

namespace App\Http\Controllers;

use App\Models\LegalAcceptance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CURRENT LEGAL DOCUMENT VERSIONS
    |--------------------------------------------------------------------------
    */

    private const EULA_VERSION = '1.0';
    private const PRIVACY_VERSION = '1.0';


    /*
    |--------------------------------------------------------------------------
    | SHOW EULA
    |--------------------------------------------------------------------------
    */

    public function showEula(Request $request): View
    {
        return view('legal.eula');
    }


    /*
    |--------------------------------------------------------------------------
    | ACCEPT EULA
    |--------------------------------------------------------------------------
    */

    public function acceptEula(Request $request): RedirectResponse
    {
        $request->validate([
            'agree' => ['accepted'],
        ]);

        LegalAcceptance::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'document_type' => 'eula',
                'document_version' => self::EULA_VERSION,
            ],
            [
                'accepted_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | AFTER EULA -> PRIVACY NOTICE
        |--------------------------------------------------------------------------
        */

        return redirect()->route('legal.privacy');
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW PRIVACY NOTICE
    |--------------------------------------------------------------------------
    */

    public function showPrivacy(Request $request): View|RedirectResponse
    {
        /*
        |--------------------------------------------------------------------------
        | REQUIRE CURRENT EULA FIRST
        |--------------------------------------------------------------------------
        */

        $eulaAccepted = LegalAcceptance::where(
                'user_id',
                $request->user()->id
            )
            ->where('document_type', 'eula')
            ->where('document_version', self::EULA_VERSION)
            ->exists();

        if (!$eulaAccepted) {
            return redirect()->route('legal.eula');
        }

        return view('legal.privacy');
    }


    /*
    |--------------------------------------------------------------------------
    | ACCEPT PRIVACY NOTICE
    |--------------------------------------------------------------------------
    */

    public function acceptPrivacy(Request $request): RedirectResponse
    {
        $request->validate([
            'acknowledge' => ['accepted'],
        ]);

        LegalAcceptance::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'document_type' => 'privacy',
                'document_version' => self::PRIVACY_VERSION,
            ],
            [
                'accepted_at' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | LEGAL ONBOARDING COMPLETE
        |--------------------------------------------------------------------------
        |
        | Send the user to RETINA Web's default authenticated page:
        | Overview.
        |
        */

        return redirect()->route('overview');
    }
}