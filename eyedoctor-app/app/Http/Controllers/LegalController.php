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
        return view('legal.eula', [
            'eulaVersion' => $this->eulaVersion(),
            'eulaLastUpdated' => (string) config('legal.eula_last_updated'),
        ]);
    }

    public function acceptEula(Request $request): RedirectResponse
    {
        $currentVersion = $this->eulaVersion();

        $validated = $request->validate([
            'agree' => ['accepted'],
            'document_version' => ['required', 'string'],
        ]);

        /*
         * A user may have opened an older agreement immediately before a
         * deployment changed the current legal version.
         *
         * Never record acceptance of a version the user was not actually
         * shown.
         */
        if ((string) $validated['document_version'] !== $currentVersion) {
            return redirect()
                ->route('legal.eula')
                ->withErrors([
                    'document_version' =>
                        'The agreement was updated while you were reviewing it. Please review the current version before accepting.',
                ]);
        }

LegalAcceptance::firstOrCreate(
    [
        'user_id' => $request->user()->id,
        'document_type' => 'eula',
        'document_version' => $currentVersion,
    ],
    [
        'accepted_at' => now(),
    ]
);

/*
 * If the user has already acknowledged the current Privacy Notice,
 * do not require them to acknowledge the unchanged document again.
 *
 * This matters when only the EULA version changes.
 */
if ($this->hasAccepted(
    $request,
    'privacy',
    $this->privacyVersion()
)) {
    return redirect()->intended(
        $this->defaultDestination($request)
    );
}

return redirect()->route('legal.privacy');

    }

    public function showPrivacy(Request $request): View|RedirectResponse
    {
        /*
         * Privacy acknowledgement comes only after the current EULA has
         * actually been accepted.
         */
        if (!$this->hasAccepted(
            $request,
            'eula',
            $this->eulaVersion()
        )) {
            return redirect()->route('legal.eula');
        }

        return view('legal.privacy', [
            'privacyVersion' => $this->privacyVersion(),
            'privacyLastUpdated' => (string) config(
                'legal.privacy_last_updated'
            ),
        ]);
    }

    public function acceptPrivacy(Request $request): RedirectResponse
    {
        /*
         * Do not rely only on showPrivacy().
         *
         * A client can submit the POST endpoint directly, so the EULA
         * prerequisite is enforced again at the mutation boundary.
         */
        if (!$this->hasAccepted(
            $request,
            'eula',
            $this->eulaVersion()
        )) {
            return redirect()->route('legal.eula');
        }

        $currentVersion = $this->privacyVersion();

        $validated = $request->validate([
            'acknowledge' => ['accepted'],
            'document_version' => ['required', 'string'],
        ]);

        /*
         * Prevent a stale Privacy Notice tab from being recorded as
         * acceptance of a newer document version.
         */
        if ((string) $validated['document_version'] !== $currentVersion) {
            return redirect()
                ->route('legal.privacy')
                ->withErrors([
                    'document_version' =>
                        'The Privacy Notice was updated while you were reviewing it. Please review the current version before continuing.',
                ]);
        }

        LegalAcceptance::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'document_type' => 'privacy',
                'document_version' => $currentVersion,
            ],
            [
                'accepted_at' => now(),
            ]
        );

        return redirect()->intended(
            $this->defaultDestination($request)
        );
    }

    private function hasAccepted(
        Request $request,
        string $documentType,
        string $documentVersion
    ): bool {
        return LegalAcceptance::query()
            ->where('user_id', $request->user()->id)
            ->where('document_type', $documentType)
            ->where('document_version', $documentVersion)
            ->exists();
    }

    private function eulaVersion(): string
    {
        return $this->configuredVersion('eula_version');
    }

    private function privacyVersion(): string
    {
        return $this->configuredVersion('privacy_version');
    }

    private function configuredVersion(string $key): string
    {
        $version = trim((string) config("legal.{$key}"));

        /*
         * Fail closed rather than silently recording an empty or malformed
         * legal version if deployment configuration is ever corrupted.
         */
        if ($version === '') {
            abort(500, 'Legal document version is not configured.');
        }

        return $version;
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