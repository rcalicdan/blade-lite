<?php

namespace Rcalicdan\BladeLite\Config;

/**
 * Advanced configuration loader with file-based config support
 * Supports environment-specific overrides and flexible path resolution
 */
class ConfigLoader
{
    protected static ?array $config = null;
    protected static ?array $rawConfig = null;
    protected static ?string $environment = null;
    protected static array $configPaths = [];
    
    /**
     * Load configuration with file-based config and auto-discovery fallback
     */
    public static function load(array $customConfig = []): array
    {
        if (self::$config !== null && empty($customConfig)) {
            return self::$config;
        }

        // Load raw config from file
        $fileConfig = self::loadConfigFile();
        
        // Merge with custom config
        $mergedConfig = array_merge($fileConfig, $customConfig);
        
        // Apply environment-specific overrides
        $environmentConfig = self::applyEnvironmentOverrides($mergedConfig);
        
        // Process and resolve paths
        self::$config = self::processConfig($environmentConfig);
        
        return self::$config;
    }
    
    /**
     * Load configuration from file
     */
    protected static function loadConfigFile(): array
    {
        if (self::$rawConfig !== null) {
            return self::$rawConfig;
        }
        
        $configFile = self::findConfigFile();
        
        if ($configFile && is_readable($configFile)) {
            self::$rawConfig = require $configFile;
        } else {
            // Fallback to default config
            self::$rawConfig = self::getDefaultConfig();
        }
        
        return self::$rawConfig;
    }
    
    /**
     * Find configuration file in common locations
     */
    protected static function findConfigFile(): ?string
    {
        $rootPath = self::findProjectRoot();
        
        $candidates = [
            $rootPath . '/config/blade.php',
            $rootPath . '/config/blade.config.php',
            $rootPath . '/blade.config.php',
            $rootPath . '/.blade.php',
            dirname(__FILE__) . '/blade.php',
        ];
        
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }
        
        return null;
    }
    
    /**
     * Get default configuration array
     */
    protected static function getDefaultConfig(): array
    {
        return [
            'viewsPath' => null,
            'cachePath' => null,
            'componentNamespace' => 'components',
            'componentPath' => null,
            'namespaces' => [],
            'debug' => null,
            'autoReload' => true,
            'extensions' => ['blade.php', 'blade.html'],
            'customDirectives' => [],
            'errorHandling' => [
                'showErrors' => null,
                'logErrors' => true,
                'errorView' => null,
            ],
            'performance' => [
                'precompileViews' => false,
                'cacheFileChecks' => true,
                'optimizeIncludes' => true,
            ],
            'security' => [
                'csrfToken' => true,
                'escapeByDefault' => true,
                'allowPhpTags' => false,
            ],
            'environments' => [],
        ];
    }
    
    /**
     * Apply environment-specific configuration overrides
     */
    protected static function applyEnvironmentOverrides(array $config): array
    {
        $environment = self::getCurrentEnvironment();
        
        if (isset($config['environments'][$environment])) {
            $envOverrides = $config['environments'][$environment];
            $config = array_merge_recursive($config, $envOverrides);
        }
        
        return $config;
    }
    
    /**
     * Process configuration and resolve null/auto-discovery values
     */
    protected static function processConfig(array $config): array
    {
        $rootPath = self::findProjectRoot();
        
        // Resolve paths
        $config['rootPath'] = $rootPath;
        $config['viewsPath'] = $config['viewsPath'] ?? self::findViewsPath($rootPath);
        $config['cachePath'] = $config['cachePath'] ?? self::findCachePath($rootPath);
        $config['componentPath'] = $config['componentPath'] ?? self::findComponentsPath($config['viewsPath']);
        
        // Resolve debug mode
        $config['debug'] = $config['debug'] ?? self::isDebugMode();
        $config['errorHandling']['showErrors'] = $config['errorHandling']['showErrors'] ?? $config['debug'];
        
        // Resolve namespaces
        $config['namespaces'] = self::resolveNamespacePaths($config['namespaces'], $config['viewsPath']);
        
        return $config;
    }
    
    /**
     * Resolve namespace paths
     */
    protected static function resolveNamespacePaths(array $namespaces, string $viewsPath): array
    {
        $resolved = [];
        
        foreach ($namespaces as $namespace => $path) {
            if ($path === null) {
                $resolved[$namespace] = $viewsPath . '/' . $namespace;
            } else {
                $resolved[$namespace] = $path;
            }
            
            // Create directory if it doesn't exist
            if (!is_dir($resolved[$namespace])) {
                mkdir($resolved[$namespace], 0755, true);
            }
        }
        
        return $resolved;
    }
    
    /**
     * Get current environment
     */
    protected static function getCurrentEnvironment(): string
    {
        if (self::$environment !== null) {
            return self::$environment;
        }
        
        // Check various environment sources
        $envSources = [
            $_ENV['APP_ENV'] ?? null,
            $_ENV['ENVIRONMENT'] ?? null,
            getenv('APP_ENV'),
            getenv('ENVIRONMENT'),
        ];
        
        foreach ($envSources as $env) {
            if ($env !== null && $env !== false) {
                self::$environment = strtolower($env);
                return self::$environment;
            }
        }
        
        // Default environment detection
        self::$environment = self::isDebugMode() ? 'development' : 'production';
        return self::$environment;
    }
    
    /**
     * Find the project root directory
     */
    protected static function findProjectRoot(): string
    {
        $current = dirname(__FILE__);
        $indicators = ['composer.json', '.git', 'vendor', 'package.json'];
        
        for ($i = 0; $i < 10; $i++) {
            foreach ($indicators as $indicator) {
                if (file_exists($current . '/' . $indicator)) {
                    return $current;
                }
            }
            
            $parent = dirname($current);
            if ($parent === $current) {
                break;
            }
            $current = $parent;
        }
        
        return getcwd() ?: dirname(__FILE__);
    }
    
    /**
     * Find views directory with common patterns
     */
    protected static function findViewsPath(string $rootPath): string
    {
        $candidates = [
            $rootPath . '/views',
            $rootPath . '/templates',
            $rootPath . '/resources/views',
            $rootPath . '/app/Views',
            $rootPath . '/src/views',
            $rootPath . '/public/views',
        ];

        foreach ($candidates as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        // Create default views directory
        $defaultPath = $rootPath . '/views';
        if (!is_dir($defaultPath)) {
            mkdir($defaultPath, 0755, true);
        }

        return $defaultPath;
    }
    
    /**
     * Find cache directory with write permissions
     */
    protected static function findCachePath(string $rootPath): string
    {
        $candidates = [
            $rootPath . '/cache/blade',
            $rootPath . '/storage/cache/blade',
            $rootPath . '/var/cache/blade',
            $rootPath . '/tmp/cache/blade',
            $rootPath . '/writable/cache/blade',
        ];

        foreach ($candidates as $path) {
            $dir = dirname($path);
            if (is_dir($dir) && is_writable($dir)) {
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                return $path;
            }
        }

        // Try system temp directory
        $tempPath = sys_get_temp_dir() . '/blade_cache_' . md5($rootPath);
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return $tempPath;
    }
    
    /**
     * Find components directory
     */
    protected static function findComponentsPath(string $viewsPath): string
    {
        $defaultPath = $viewsPath . '/components';
        
        if (!is_dir($defaultPath)) {
            mkdir($defaultPath, 0755, true);
        }
        
        return $defaultPath;
    }
    
    /**
     * Detect debug mode from environment
     */
    protected static function isDebugMode(): bool
    {
        $debugIndicators = [
            $_ENV['APP_DEBUG'] ?? null,
            $_ENV['DEBUG'] ?? null,
            getenv('APP_DEBUG'),
            getenv('DEBUG'),
        ];

        foreach ($debugIndicators as $indicator) {
            if ($indicator !== null && $indicator !== false) {
                return in_array(strtolower($indicator), ['true', '1', 'yes', 'on']);
            }
        }

        return !in_array(self::getCurrentEnvironment(), ['production', 'prod']);
    }
    
    /**
     * Set custom configuration paths
     */
    public static function addConfigPath(string $path): void
    {
        if (!in_array($path, self::$configPaths)) {
            array_unshift(self::$configPaths, $path);
        }
    }
    
    /**
     * Override configuration values at runtime
     */
    public static function override(array $config): void
    {
        self::$config = array_merge_recursive(self::load(), $config);
    }
    
    /**
     * Get a specific configuration value
     */
    public static function get(string $key, $default = null)
    {
        $config = self::load();
        
        // Support dot notation
        $keys = explode('.', $key);
        $value = $config;
        
        foreach ($keys as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    /**
     * Set environment for testing
     */
    public static function setEnvironment(string $environment): void
    {
        self::$environment = $environment;
        self::reset(); // Reset config to apply new environment
    }
    
    /**
     * Reset configuration cache
     */
    public static function reset(): void
    {
        self::$config = null;
        self::$rawConfig = null;
    }
    
    /**
     * Create a sample configuration file
     */
    public static function createSampleConfig(string $path = null): bool
    {
        $path = $path ?? (self::findProjectRoot() . '/config/blade.php');
        
        // Ensure directory exists
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Get sample config content
        $sampleConfig = file_get_contents(__DIR__ . '/blade.php');
        
        return file_put_contents($path, $sampleConfig) !== false;
    }
}