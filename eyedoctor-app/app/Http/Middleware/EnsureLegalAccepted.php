<?php

namespace App\Http\Middleware;

use App\Models\LegalAcceptance;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureLegalAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $eulaVersion = (string) config('legal.eula_version');
        $privacyVersion = (string) config('legal.privacy_version');

        $eulaAccepted = LegalAcceptance::query()
            ->where('user_id', $user->id)
            ->where('document_type', 'eula')
            ->where('document_version', $eulaVersion)
            ->exists();

        if (!$eulaAccepted) {
            $this->rememberIntendedGetRequest($request);

            return redirect()->route('legal.eula');
        }

        $privacyAccepted = LegalAcceptance::query()
            ->where('user_id', $user->id)
            ->where('document_type', 'privacy')
            ->where('document_version', $privacyVersion)
            ->exists();

        if (!$privacyAccepted) {
            $this->rememberIntendedGetRequest($request);

            return redirect()->route('legal.privacy');
        }

        return $next($request);
    }

    private function rememberIntendedGetRequest(Request $request): void
    {
        /*
         * Preserve safe GET destinations such as /screening or /history so the
         * user can continue where they were going after legal onboarding.
         *
         * Never preserve POST/PATCH/DELETE requests as an intended destination.
         * Replaying them as GET requests after acceptance would be incorrect
         * and could produce confusing or unsafe application behavior.
         */
        if ($request->isMethod('GET')) {
            $request->session()->put('url.intended', $request->fullUrl());
        }
    }
}