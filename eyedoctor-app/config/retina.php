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
            'releases/retina-latest.apk'
        ),

        'download_name' => env(
            'RETINA_APK_DOWNLOAD_NAME',
            'RETINA-Android.apk'
        ),

    ],

];