<?php

namespace Rcalicdan\BladeLite;

use Rcalicdan\Blade\Blade;
use Rcalicdan\Blade\Container as BladeContainer;
use Rcalicdan\BladeLite\Config\ConfigLoader;

/**
 * BladeService that relies on a validated configuration from ConfigLoader.
 */
class BladeService
{
    protected Blade $blade;
    protected array $config;
    protected BladeExtension $bladeExtension;
    protected array $viewData = [];
    protected bool $extensionsLoaded = false;

    public function __construct(array $customConfig = [])
    {
        $this->config = ConfigLoader::load($customConfig);
        $this->bladeExtension = new BladeExtension();
        $this->initialize();
    }

    protected function initialize(): void
    {
        $this->ensureDirectoriesExist();

        $container = new BladeContainer();

        $this->blade = new Blade(
            $this->config['viewsPath'],
            $this->config['cachePath'],
            $container
        );

        if (!empty($this->config['componentPath']) && is_dir($this->config['componentPath'])) {
            $this->blade->addNamespace(
                $this->config['componentNamespace'],
                $this->config['componentPath']
            );
        }
        
        foreach ($this->config['namespaces'] as $namespace => $path) {
            if (!empty($path) && is_dir($path)) {
                $this->blade->addNamespace($namespace, $path);
            }
        }
    }

    /**
     * Ensures the configured directories exist.
     */
    protected function ensureDirectoriesExist(): void
    {
        // The ConfigLoader now guarantees these are valid strings.
        $viewsPath = $this->config['viewsPath'];
        $cachePath = $this->config['cachePath'];

        if (!is_dir($viewsPath)) {
            if (!mkdir($viewsPath, 0755, true)) {
                throw new \RuntimeException("Cannot create views directory: {$viewsPath}");
            }
        }

        if (!is_dir($cachePath)) {
            if (!mkdir($cachePath, 0755, true)) {
                throw new \RuntimeException("Cannot create cache directory: {$cachePath}");
            }
        }

        if (!is_writable($cachePath)) {
            throw new \RuntimeException("Blade cache path is not writable: {$cachePath}");
        }

        // Handle optional component path
        if (empty($this->config['componentPath'])) {
            $this->config['componentPath'] = $this->config['viewsPath'] . '/components';
        }
        if (!is_dir($this->config['componentPath'])) {
            @mkdir($this->config['componentPath'], 0755, true);
        }
    }

    // ... (The rest of the BladeService class remains the same)
    // You don't need to re-paste everything from applyExtensions() downwards.
    
    protected function applyExtensions(): void
    {
        if ($this->extensionsLoaded) {
            return;
        }

        $this->bladeExtension->registerDirectives($this->blade);
        
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
            return $this->blade->make($view, $processedData)->render();
        } catch (\Throwable $e) {
            if (!empty($this->config['errorHandling']['showErrors'])) {
                throw $e;
            }
            
            if (!empty($this->config['errorHandling']['logErrors'])) {
                error_log("Blade rendering error in view [{$view}]: {$e->getMessage()}");
            }
            
            if (!empty($this->config['errorHandling']['errorView'])) {
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