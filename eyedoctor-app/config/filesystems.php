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
        | Retinal Image Storage — Supabase
        |--------------------------------------------------------------------------
        |
        | Private Supabase Storage bucket used for retinal fundus images.
        | These credentials remain completely separate from Cloudflare R2.
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
        | RETINA Android Downloads — Cloudflare R2
        |--------------------------------------------------------------------------
        |
        | Private Cloudflare R2 bucket containing approved RETINA Android
        | release packages.
        |
        | This disk uses a dedicated read-only R2 credential and does not
        | share the Supabase retinal-image credentials above.
        |
        */

        'app_downloads' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => 'auto',
            'bucket' => env(
                'R2_BUCKET',
                'retina-app-downloads'
            ),
            'endpoint' => env('R2_ENDPOINT'),
            'use_path_style_endpoint' => true,
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