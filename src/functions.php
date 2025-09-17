<?php

use Rcalicdan\BladeLite\BladeService;
use Rcalicdan\BladeLite\BladeViewRenderer;
use Rcalicdan\Ci4Larabridge\Config\ConfigLoader;

if (!function_exists('blade_view')) {
    function blade_view(?string $view = null, array $data = []): BladeViewRenderer|string
    {
        static $renderer = null;

        if ($renderer === null) {
            $service = BladeService::getInstance();
            $renderer = new BladeViewRenderer($service);
        }

        if ($view !== null) {
            return $renderer->view($view)->with($data)->render();
        }

        return $renderer;
    }
}

if (!function_exists('blade_config')) {
    function blade_config(?string $key = null, $default = null)
    {
        if ($key === null) {
            return ConfigLoader::load();
        }
        
        return ConfigLoader::get($key, $default);
    }
}

if (!function_exists('blade_service')) {
    function blade_service(): BladeService
    {
        return BladeService::getInstance();
    }
}

if (!function_exists('blade_namespace')) {
    function blade_namespace(string $namespace, string $path): void
    {
        blade_service()->getBlade()->addNamespace($namespace, $path);
    }
}

if (!function_exists('blade_directive')) {
    function blade_directive(string $name, callable $callback): void
    {
        blade_service()->getBlade()->directive($name, $callback);
    }
}