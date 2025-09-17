<?php

namespace Rcalicdan\BladeLite;

use Rcalicdan\Blade\Blade;
use Rcalicdan\Blade\Container as BladeContainer;
use Rcalicdan\BladeLite\Config\ConfigLoader;

/**
 * Enhanced BladeService with file-based configuration support
 */
class BladeService
{
    protected Blade $blade;
    protected array $config;
    protected BladeExtension $bladeExtension;
    protected array $viewData = [];
    protected array $fileExistsCache = [];
    protected bool $extensionsLoaded = false;

    public function __construct(array $customConfig = [])
    {
        // Load configuration from file with custom overrides
        $this->config = ConfigLoader::load($customConfig);
        $this->bladeExtension = new BladeExtension();
        $this->initialize();
    }

    protected function initialize(): void
    {
        // Ensure we have valid paths before proceeding
        $this->validatePaths();
        $this->ensureCacheDirectory();

        $container = new BladeContainer();

        $this->blade = new Blade(
            $this->config['viewsPath'],
            $this->config['cachePath'],
            $container
        );

        // Only add component namespace if path exists and is valid
        if (!empty($this->config['componentPath']) && is_dir($this->config['componentPath'])) {
            $this->blade->addNamespace(
                $this->config['componentNamespace'],
                $this->config['componentPath']
            );
        }
        
        // Add other namespaces if they exist
        foreach ($this->config['namespaces'] as $namespace => $path) {
            if (!empty($path) && is_dir($path)) {
                $this->blade->addNamespace($namespace, $path);
            }
        }
    }

    protected function validatePaths(): void
    {
        if (empty($this->config['viewsPath'])) {
            $this->config['viewsPath'] = $this->getDefaultViewsPath();
        }

        if (empty($this->config['cachePath'])) {
            $this->config['cachePath'] = $this->getDefaultCachePath();
        }

        if (empty($this->config['componentPath'])) {
            $this->config['componentPath'] = $this->config['viewsPath'] . '/components';
        }

        if (!is_dir($this->config['viewsPath'])) {
            if (!mkdir($this->config['viewsPath'], 0755, true)) {
                throw new \RuntimeException("Cannot create views directory: {$this->config['viewsPath']}");
            }
        }

        if (!is_dir($this->config['componentPath'])) {
            mkdir($this->config['componentPath'], 0755, true);
        }
    }

    protected function getDefaultViewsPath(): string
    {
        $candidates = [
            getcwd() . '/views',
            getcwd() . '/templates',
            getcwd() . '/resources/views',
        ];

        foreach ($candidates as $path) {
            if (is_dir($path)) {
                return $path;
            }
        }

        // Create default views directory
        $defaultPath = getcwd() . '/views';
        if (!is_dir($defaultPath)) {
            mkdir($defaultPath, 0755, true);
        }

        return $defaultPath;
    }

    protected function getDefaultCachePath(): string
    {
        $candidates = [
            getcwd() . '/cache/blade',
            getcwd() . '/storage/cache/blade',
            getcwd() . '/tmp/blade',
        ];

        foreach ($candidates as $path) {
            $dir = dirname($path);
            if (is_writable($dir) || @mkdir($dir, 0755, true)) {
                if (!is_dir($path)) {
                    @mkdir($path, 0755, true);
                }
                if (is_writable($path)) {
                    return $path;
                }
            }
        }

        // Use system temp directory as fallback
        $tempPath = sys_get_temp_dir() . '/blade_cache_' . md5(getcwd());
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        return $tempPath;
    }

    protected function ensureCacheDirectory(): void
    {
        $cachePath = $this->config['cachePath'];

        // Double check we have a valid cache path
        if (empty($cachePath)) {
            throw new \RuntimeException("Cache path cannot be empty");
        }

        if (!isset($this->fileExistsCache[$cachePath])) {
            if (!is_dir($cachePath)) {
                if (!mkdir($cachePath, 0755, true)) {
                    throw new \RuntimeException("Cannot create cache directory: {$cachePath}");
                }
            }
            $this->fileExistsCache[$cachePath] = true;
        }

        if (!is_writable($cachePath)) {
            throw new \RuntimeException("Blade cache path is not writable: {$cachePath}");
        }
    }

    protected function applyExtensions(): void
    {
        if ($this->extensionsLoaded) {
            return;
        }

        // Register built-in directives
        $this->bladeExtension->registerDirectives($this->blade);
        
        // Register custom directives from config
        if (!empty($this->config['customDirectives'])) {
            foreach ($this->config['customDirectives'] as $name => $callback) {
                $this->blade->directive($name, $callback);
            }
        }

        $this->extensionsLoaded = true;
    }

    public function processData(array $data): array
    {
        return $this->bladeExtension->processData($data);
    }

    public function setData(array $data = []): self
    {
        $this->viewData = $this->processData($data);
        return $this;
    }

    public function render(string $view, array $data = []): string
    {
        $this->applyExtensions();

        $mergedData = array_merge($this->viewData ?? [], $data);
        $processedData = $this->processData($mergedData);

        try {
            $result = $this->blade->make($view, $processedData)->render();
            return $result;
        } catch (\Throwable $e) {
            if ($this->config['errorHandling']['showErrors']) {
                throw $e;
            }
            
            if ($this->config['errorHandling']['logErrors']) {
                error_log("Blade rendering error in view [{$view}]: {$e->getMessage()}");
            }
            
            if ($this->config['errorHandling']['errorView']) {
                try {
                    return $this->blade->make($this->config['errorHandling']['errorView'], [
                        'error' => $e,
                        'view' => $view
                    ])->render();
                } catch (\Throwable $errorViewException) {
                    // Fallback if error view also fails
                }
            }
            
            return '<!-- View Rendering Error -->';
        } finally {
            $this->viewData = [];
        }
    }

    public function getBlade(): Blade
    {
        return $this->blade;
    }

    public function getConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }
        
        return ConfigLoader::get($key, $default);
    }

    public static function getInstance(array $config = []): self
    {
        static $instance = null;
        
        if ($instance === null) {
            $instance = new self($config);
        }
        
        return $instance;
    }
}