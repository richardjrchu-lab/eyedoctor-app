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

            $stream = $disk->readStream($apkPath);

            if ($stream === false) {
                throw new \RuntimeException(
                    'Unable to open the APK storage stream.'
                );
            }

            Log::info('RETINA Android application downloaded.', [
                'user_id' => $user->id,
            ]);

            return response()->streamDownload(
                function () use ($stream) {
                    fpassthru($stream);

                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                },
                $downloadName,
                [
                    'Content-Type' => 'application/vnd.android.package-archive',
                    'Cache-Control' => 'private, no-store, max-age=0',
                    'Pragma' => 'no-cache',
                    'X-Content-Type-Options' => 'nosniff',
                ]
            );
        } catch (\Throwable $e) {
            Log::error('RETINA APK download failed.', [
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