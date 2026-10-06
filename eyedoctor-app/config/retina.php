<?php

return [

    'mobile' => [

        /*
        |--------------------------------------------------------------------------
        | RETINA Android Application
        |--------------------------------------------------------------------------
        |
        | These values describe the approved release build of the RETINA
        | Android application. They can be changed through environment
        | variables without modifying application code.
        |
        */

        'version' => env(
            'RETINA_APP_VERSION'
        ),

        'version_code' => env(
            'RETINA_APP_VERSION_CODE'
        ),

        'package_id' => env(
            'RETINA_APP_PACKAGE_ID'
        ),

        /*
        |--------------------------------------------------------------------------
        | Private APK Storage Path
        |--------------------------------------------------------------------------
        |
        | The APK is NOT stored inside Laravel's public directory.
        | It is stored in the private app-downloads Supabase bucket and
        | delivered only through the authenticated Laravel download route.
        |
        */

        'apk_path' => env(
            'RETINA_APK_PATH',
            'releases/android/2026-10-06/RETINA-Android.apk'
        ),

        'download_name' => env(
            'RETINA_APK_DOWNLOAD_NAME',
            'RETINA-Android.apk'
        ),

    ],


    /*
    |--------------------------------------------------------------------------
    | RETINA Windows Application
    |--------------------------------------------------------------------------
    |
    | The Windows release is stored in the same private Cloudflare R2
    | application-download bucket as the Android release.
    |
    */

    'windows' => [

        'version' => env(
            'RETINA_WINDOWS_VERSION'
        ),

        'package_path' => env(
            'RETINA_WINDOWS_PATH',
            'releases/windows/2026-10-06/RETINA-Windows.zip'
        ),

        'download_name' => env(
            'RETINA_WINDOWS_DOWNLOAD_NAME',
            'RETINA-Windows.zip'
        ),

    ],

];
