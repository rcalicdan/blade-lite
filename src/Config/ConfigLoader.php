<?php

namespace Rcalicdan\BladeLite\Config;

/**
 * Simplified configuration loader - explicit config only, no auto-discovery
 */
class ConfigLoader
{
    protected static ?array $config = null;
    
    /**
     * Load configuration from file with custom overrides
     */
    public static function load(array $customConfig = []): array
    {
        if (self::$config !== null && empty($customConfig)) {
            return self::$config;
        }

        $fileConfig = self::loadConfigFile();
        
        self::$config = array_merge($fileConfig, $customConfig);
        
        self::applyEnvironmentOverrides();
        
        return self::$config;
    }
    
    /**
     * Load configuration from file
     */
    protected static function loadConfigFile(): array
    {
        $configFile = self::findConfigFile();
        
        if ($configFile && is_readable($configFile)) {
            return require $configFile;
        }
        
        return self::getDefaultConfig();
    }
    
    /**
     * Find configuration file
     */
    protected static function findConfigFile(): ?string
    {
        $candidates = [
            getcwd() . '/config/blade.php',
            dirname(__DIR__, 2) . '/config/blade.php', 
        ];
        
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }
        
        return null;
    }
    
    /**
     * Get basic default configuration
     */
    protected static function getDefaultConfig(): array
    {
        return [
            'viewsPath' => getcwd() . '/views',
            'cachePath' => sys_get_temp_dir() . '/blade_cache',
            'componentNamespace' => 'components',
            'componentPath' => getcwd() . '/views/components',
            'namespaces' => [],
            'debug' => true,
            'autoReload' => true,
            'extensions' => ['blade.php', 'blade.html'],
            'customDirectives' => [],
            'errorHandling' => [
                'showErrors' => true,
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
     * Apply environment-specific overrides
     */
    protected static function applyEnvironmentOverrides(): void
    {
        if (!isset(self::$config['environments'])) {
            return;
        }
        
        $environment = self::getCurrentEnvironment();
        
        if (isset(self::$config['environments'][$environment])) {
            $envOverrides = self::$config['environments'][$environment];
            self::$config = array_merge_recursive(self::$config, $envOverrides);
        }
    }
    
    /**
     * Get current environment
     */
    protected static function getCurrentEnvironment(): string
    {
        return $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';
    }
    
    /**
     * Get a configuration value using dot notation
     */
    public static function get(string $key, $default = null)
    {
        $config = self::load();
        
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
     * Reset configuration cache
     */
    public static function reset(): void
    {
        self::$config = null;
    }
}