<?php

namespace Rcalicdan\BladeLite\Config;

/**
 * A robust, self-configuring loader that finds the project root
 * by searching for the 'vendor' directory, ensuring paths are always correct.
 */
class ConfigLoader
{
    protected static ?array $config = null;
    protected static ?string $rootPath = null;

    public static function load(array $customConfig = []): array
    {
        if (self::$config !== null && empty($customConfig)) {
            return self::$config;
        }

        $rootPath = self::findProjectRoot();
        $defaults = self::getDefaultConfig($rootPath);
        $fileConfig = self::loadConfigFile($rootPath);
        
        $mergedConfig = array_replace_recursive($defaults, $fileConfig, $customConfig);
        
        self::$config = $mergedConfig;
        
        self::applyEnvironmentOverrides();
        self::validateRequiredPaths(self::$config);
        
        return self::$config;
    }

    protected static function findProjectRoot(): string
    {
        if (self::$rootPath !== null) {
            return self::$rootPath;
        }

        $dir = __DIR__;
        for ($i = 0; $i < 10; $i++) {
            if (is_dir($dir . '/vendor')) {
                return self::$rootPath = $dir;
            }
            $parentDir = dirname($dir);
            if ($parentDir === $dir) {
                break;
            }
            $dir = $parentDir;
        }

        throw new \RuntimeException('Could not find the project root. The `vendor` directory is missing.');
    }

    protected static function loadConfigFile(string $rootPath): array
    {
        $configFile = $rootPath . '/config/blade.php';
        
        if (!file_exists($configFile)) {
            throw new \RuntimeException("Blade configuration file not found at: {$configFile}");
        }
        if (!is_readable($configFile)) {
            throw new \RuntimeException("Blade configuration file is not readable: {$configFile}");
        }
        
        $config = require $configFile;
        return is_array($config) ? $config : [];
    }
    
    protected static function validateRequiredPaths(array $config): void
    {
        $requiredPaths = ['viewsPath', 'cachePath'];
        foreach ($requiredPaths as $key) {
            if (empty($config[$key]) || !is_string($config[$key])) {
                throw new \InvalidArgumentException(
                    "Configuration value for '{$key}' must be a non-empty string. Please check your 'config/blade.php'."
                );
            }
        }
    }
    
    /**
     * Gets default values for non-critical settings.
     * Critical paths are set to null to ensure they are defined in the config file.
     */
    protected static function getDefaultConfig(string $rootPath): array
    {
        return [
            'viewsPath' => null,
            'cachePath' => null,
            'componentPath' => null,
            'componentNamespace' => 'components',
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

    protected static function applyEnvironmentOverrides(): void
    {
        if (!isset(self::$config['environments'])) return;
        
        $environment = self::getCurrentEnvironment();
        
        if (isset(self::$config['environments'][$environment])) {
            $envOverrides = self::$config['environments'][$environment];
            self::$config = array_replace_recursive(self::$config, $envOverrides);
        }
    }
    
    protected static function getCurrentEnvironment(): string
    {
        return $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development';
    }

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

    public static function reset(): void
    {
        self::$config = null;
        self::$rootPath = null;
    }
}