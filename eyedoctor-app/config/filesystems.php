<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),


    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],


        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => rtrim(
                env('APP_URL', 'http://localhost'),
                '/'
            ).'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],


        /*
        |--------------------------------------------------------------------------
        | Retinal Image Storage
        |--------------------------------------------------------------------------
        |
        | Existing private Supabase bucket used for retinal fundus images.
        |
        */

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env(
                'AWS_USE_PATH_STYLE_ENDPOINT',
                false
            ),
            'throw' => true,
            'report' => false,
        ],


        /*
        |--------------------------------------------------------------------------
        | RETINA Private Application Downloads
        |--------------------------------------------------------------------------
        |
        | Separate private Supabase bucket containing approved application
        | release packages. Files from this disk are never linked publicly.
        |
        | Access is provided only through Laravel's authenticated,
        | role-protected download route.
        |
        */

        'app_downloads' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('APP_DOWNLOADS_BUCKET'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env(
                'AWS_USE_PATH_STYLE_ENDPOINT',
                false
            ),
            'visibility' => 'private',
            'throw' => true,
            'report' => false,
        ],

    ],


    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [

        public_path('storage') => storage_path('app/public'),

    ],

];