<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LegalAcceptance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
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
    | SHOW LOGIN PAGE
    |--------------------------------------------------------------------------
    */

    public function create(): View
    {
        return view('auth.login');
    }


    /*
    |--------------------------------------------------------------------------
    | HANDLE LOGIN
    |--------------------------------------------------------------------------
    */

    public function store(LoginRequest $request): RedirectResponse
    {
        /*
        |--------------------------------------------------------------------------
        | AUTHENTICATE USER
        |--------------------------------------------------------------------------
        */

        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();


        /*
        |--------------------------------------------------------------------------
        | CHECK CURRENT EULA
        |--------------------------------------------------------------------------
        */

        $eulaAccepted = LegalAcceptance::where(
                'user_id',
                $user->id
            )
            ->where('document_type', 'eula')
            ->where('document_version', self::EULA_VERSION)
            ->exists();


        /*
        |--------------------------------------------------------------------------
        | NEW USER / UPDATED EULA
        |--------------------------------------------------------------------------
        */

        if (!$eulaAccepted) {
            return redirect()->route('legal.eula');
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK CURRENT PRIVACY NOTICE
        |--------------------------------------------------------------------------
        */

        $privacyAccepted = LegalAcceptance::where(
                'user_id',
                $user->id
            )
            ->where('document_type', 'privacy')
            ->where('document_version', self::PRIVACY_VERSION)
            ->exists();


        /*
        |--------------------------------------------------------------------------
        | PRIVACY NOT YET ACKNOWLEDGED
        |--------------------------------------------------------------------------
        */

        if (!$privacyAccepted) {
            return redirect()->route('legal.privacy');
        }


        /*
        |--------------------------------------------------------------------------
        | RETURNING USER
        |--------------------------------------------------------------------------
        |
        | Both current legal documents have already been accepted.
        | Send the user directly to RETINA Web Overview.
        |
        */

        return redirect()->route('overview');
    }


    /*
    |--------------------------------------------------------------------------
    | LOGOUT
    |--------------------------------------------------------------------------
    */

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        /*
        |--------------------------------------------------------------------------
        | RETURN TO PUBLIC ENTRY
        |--------------------------------------------------------------------------
        */

        return redirect()->route('welcome');
    }
}