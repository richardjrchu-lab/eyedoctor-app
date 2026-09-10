<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MobileAppController extends Controller
{
    public function index()
    {
        $apkAvailable = false;

        try {
            $bucket = config('filesystems.disks.app_downloads.bucket');
            $apkPath = config('retina.mobile.apk_path');

            if ($bucket && $apkPath) {
                $apkAvailable = Storage::disk('app_downloads')->exists($apkPath);
            }
        } catch (\Throwable $e) {
            Log::warning('Unable to check RETINA APK availability.', [
                'error' => $e->getMessage(),
            ]);

            $apkAvailable = false;
        }

        return view('mobile-app', [
            'apkAvailable' => $apkAvailable,
            'appVersion' => config('retina.mobile.version'),
            'versionCode' => config('retina.mobile.version_code'),
            'packageId' => config('retina.mobile.package_id'),
        ]);
    }

    public function download(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user && $user->hasAnyRole(['doctor', 'admin']),
            403
        );

        $apkPath = config('retina.mobile.apk_path');

        $downloadName = config(
            'retina.mobile.download_name',
            'RETINA-Android.apk'
        );

        try {
            $disk = Storage::disk('app_downloads');

            if (! $apkPath || ! $disk->exists($apkPath)) {
                return redirect()
                    ->route('mobile-app')
                    ->with(
                        'apk_error',
                        'The Android application package is not available yet.'
                    );
            }

            /*
             * Generate a short-lived signed Cloudflare R2 URL.
             *
             * Laravel performs authentication and authorization first.
             * The APK is then downloaded directly from the private R2
             * bucket instead of being proxied through the Render server.
             */
            $temporaryUrl = $disk->temporaryUrl(
                $apkPath,
                now()->addMinutes(5),
                [
                    'ResponseContentType' =>
                        'application/vnd.android.package-archive',

                    'ResponseContentDisposition' =>
                        'attachment; filename="' . $downloadName . '"',
                ]
            );

            Log::info('RETINA Android download authorized.', [
                'user_id' => $user->id,
                'expires_in_minutes' => 5,
            ]);

            return redirect()->away($temporaryUrl);
        } catch (\Throwable $e) {
            Log::error('RETINA APK download authorization failed.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('mobile-app')
                ->with(
                    'apk_error',
                    'The Android application is temporarily unavailable. Please try again later.'
                );
        }
    }
}
