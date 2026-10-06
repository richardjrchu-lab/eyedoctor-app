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
         * Preserve only safe GET destinations so the user can return to the
         * page they originally requested after completing legal onboarding.
         *
         * Store only the internal request URI rather than an absolute URL.
         * This prevents request Host headers from influencing the eventual
         * intended redirect destination.
         *
         * POST, PATCH, PUT, and DELETE requests are deliberately not stored.
         * Replaying a state-changing request as a later GET redirect would be
         * semantically incorrect.
         */
        if ($request->isMethod('GET')) {
            $request->session()->put(
                'url.intended',
                $request->getRequestUri()
            );
        }
    }
}
