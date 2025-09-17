<?php
// config/blade.php

return [
    /*
    |--------------------------------------------------------------------------
    | View Paths
    |--------------------------------------------------------------------------
    |
    | Specify the paths where your Blade templates are stored. You can define
    | multiple paths and they will be searched in order. If null, the system
    | will auto-discover common view directory patterns.
    |
    */
    'viewsPath' => null, // Auto-discovery if null
    
    /*
    |--------------------------------------------------------------------------
    | Cache Path
    |--------------------------------------------------------------------------
    |
    | This is where compiled Blade templates will be cached. If null, the
    | system will find or create a writable cache directory automatically.
    |
    */
    'cachePath' => null, // Auto-discovery if null
    
    /*
    |--------------------------------------------------------------------------
    | Component Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Blade components namespace and path. Components allow you to
    | create reusable UI elements.
    |
    */
    'componentNamespace' => 'components',
    'componentPath' => null, // Auto-discovery if null (usually views/components)
    
    /*
    |--------------------------------------------------------------------------
    | Additional View Namespaces
    |--------------------------------------------------------------------------
    |
    | Register additional namespaces for organizing your views. Each namespace
    | maps to a directory path.
    |
    | Example: 'admin' => '/path/to/admin/views'
    |
    */
    'namespaces' => [
        // 'admin' => null, // Will use views/admin if null
        // 'emails' => null, // Will use views/emails if null
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When debug is enabled, detailed error messages and stack traces will be
    | shown. If null, it will be auto-detected from environment variables.
    |
    */
    'debug' => null, // Auto-detection if null
    
    /*
    |--------------------------------------------------------------------------
    | Auto Reload
    |--------------------------------------------------------------------------
    |
    | Automatically recompile templates when they change. Useful for development
    | but should be disabled in production for better performance.
    |
    */
    'autoReload' => true,
    
    /*
    |--------------------------------------------------------------------------
    | File Extensions
    |--------------------------------------------------------------------------
    |
    | Define which file extensions should be treated as Blade templates.
    |
    */
    'extensions' => [
        'blade.php',
        'blade.html',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Custom Directives
    |--------------------------------------------------------------------------
    |
    | Register custom Blade directives. You can define them as closures or
    | reference class methods.
    |
    */
    'customDirectives' => [
        //
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Configure how errors are handled in templates.
    |
    */
    'errorHandling' => [
        'showErrors' => null, // Auto-detect from debug mode if null
        'logErrors' => true,
        'errorView' => null, // Custom error template
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Optimize performance for production environments.
    |
    */
    'performance' => [
        'precompileViews' => false, // Precompile all views on startup
        'cacheFileChecks' => true, // Cache file existence checks
        'optimizeIncludes' => true, // Optimize @include directives
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security-related features.
    |
    */
    'security' => [
        'csrfToken' => true, // Enable CSRF token generation
        'escapeByDefault' => true, // Auto-escape variables
        'allowPhpTags' => false, // Allow raw PHP in templates
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Environment-Specific Overrides
    |--------------------------------------------------------------------------
    |
    | Define configuration overrides for specific environments.
    |
    */
    'environments' => [
        'production' => [
            'debug' => false,
            'autoReload' => false,
            'performance' => [
                'precompileViews' => true,
                'cacheFileChecks' => true,
            ],
        ],
        
        'development' => [
            'debug' => true,
            'autoReload' => true,
            'errorHandling' => [
                'showErrors' => true,
            ],
        ],
        
        'testing' => [
            'cachePath' => '/tmp/blade_test_cache',
            'performance' => [
                'cacheFileChecks' => false,
            ],
        ],
    ],
];