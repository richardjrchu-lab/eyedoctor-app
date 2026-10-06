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
            'RETINA_APP_VERSION',
            'Pending release'
        ),

        'version_code' => env(
            'RETINA_APP_VERSION_CODE',
            'Pending'
        ),

        'package_id' => env(
            'RETINA_APP_PACKAGE_ID',
            'Pending'
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
            'releases/android/RETINA-v1.0.0-Research-Prototype.apk'
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
    | The Windows release uses an externally configured HTTPS download
    | link. If no URL is configured, the website safely shows the
    | Windows release as unavailable.
    |
    */

    'windows' => [

        'version' => env(
            'RETINA_WINDOWS_VERSION'
        ),

        'package_path' => env(
            'RETINA_WINDOWS_PATH',
            'releases/windows/RETINA_WINDOWS_FINAL.zip'
        ),

        'download_name' => env(
            'RETINA_WINDOWS_DOWNLOAD_NAME',
            'RETINA_WINDOWS_FINAL.zip'
        ),

    ],
];