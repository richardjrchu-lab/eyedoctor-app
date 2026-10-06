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

        $eulaAccepted = LegalAcceptance::where('user_id', $user->id)
            ->where('document_type', 'eula')
            ->where('document_version', '1.0')
            ->exists();

        if (!$eulaAccepted) {
            return redirect()->route('legal.eula');
        }

        $privacyAccepted = LegalAcceptance::where('user_id', $user->id)
            ->where('document_type', 'privacy')
            ->where('document_version', '1.0')
            ->exists();

        if (!$privacyAccepted) {
            return redirect()->route('legal.privacy');
        }

        return $next($request);
    }
}