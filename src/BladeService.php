<?php

namespace Rcalicdan\Ci4Larabridge\Blade;

use Rcalicdan\Blade\Blade;
use Rcalicdan\Blade\Container as BladeContainer;
use Rcalicdan\Ci4Larabridge\Config\ConfigLoader;

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
        $this->ensureCacheDirectory();

        $container = new BladeContainer();

        $this->blade = new Blade(
            $this->config['viewsPath'],
            $this->config['cachePath'],
            $container
        );

        if (is_dir($this->config['componentPath'])) {
            $this->blade->addNamespace(
                $this->config['componentNamespace'],
                $this->config['componentPath']
            );
        }
        
        foreach ($this->config['namespaces'] as $namespace => $path) {
            if (is_dir($path)) {
                $this->blade->addNamespace($namespace, $path);
            }
        }
    }

    protected function ensureCacheDirectory(): void
    {
        $cachePath = $this->config['cachePath'];

        if (!isset($this->fileExistsCache[$cachePath])) {
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0755, true);
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
        foreach ($this->config['customDirectives'] as $name => $callback) {
            $this->blade->directive($name, $callback);
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