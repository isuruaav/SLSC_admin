<?php

return [
    'name' => env('AWCMS_NAME', 'AWCMS'),

    'full_name' => env(
        'AWCMS_FULL_NAME',
        'Army Website Content Management System'
    ),

    'version' => env('AWCMS_VERSION', '1.0.0-dev'),

    'support_email' => env(
        'AWCMS_SUPPORT_EMAIL',
        'support@example.com'
    ),

    /*
    |--------------------------------------------------------------------------
    | Active Public Theme
    |--------------------------------------------------------------------------
    |
    | Keep this null to use the default AWCMS public views. Installed themes
    | are selected by their safe manifest slug, for example slsc.
    |
    */
    'active_theme' => env('AWCMS_ACTIVE_THEME', 'slsc'),

    /*
    |--------------------------------------------------------------------------
    | Theme Locales
    |--------------------------------------------------------------------------
    |
    | AWCMS core supports English, Sinhala and Tamil. Individual themes may
    | set initial defaults. The enabled languages saved in Site Settings
    | override these defaults without changing theme files.
    |
    */
    'theme_locales' => [
        'slsc' => ['en', 'si'],
    ],
];
