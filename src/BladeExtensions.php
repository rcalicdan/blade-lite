<?php

namespace Rcalicdan\Ci4Larabridge\Blade;

use Rcalicdan\Blade\Blade;

/**
 * Blade Extension with framework-agnostic features
 */
class BladeExtension
{
    protected array $methodMap = [
        'delete' => 'DELETE',
        'put' => 'PUT',
        'patch' => 'PATCH',
    ];

    public function processData(array $data): array
    {
        return $this->addErrorsHandler($data);
    }

    public function registerDirectives(Blade $blade): void
    {
        $this->_registerMethodDirectives($blade);
        $this->_registerPermissionDirectives($blade);
        $this->_registerErrorDirectives($blade);
        $this->_registerAuthDirectives($blade);
        $this->_registerLangDirectives($blade);
        $this->_registerFragmentDirectives($blade);
        $this->_registerUtilityDirectives($blade);
        $this->_registerConditionalDirectives($blade);
    }

    protected function addErrorsHandler(array $data): array
    {
        if (!isset($data['errors'])) {
            $sessionErrors = $this->getSessionErrors();
            if (!empty($sessionErrors) && is_array($sessionErrors)) {
                $data['errors'] = new ErrorBag($sessionErrors);
            }
        }

        return $data;
    }

    protected function getSessionErrors(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return $_SESSION['errors'] ?? [];
        }
        
        return [];
    }

    private function _registerMethodDirectives(Blade $blade): void
    {
        $blade->directive('method', function ($expression) {
            $method = strtoupper(trim($expression, "()\"'"));
            return "<input type=\"hidden\" name=\"_method\" value=\"{$method}\">";
        });

        foreach ($this->methodMap as $directive => $method) {
            $blade->directive($directive, fn () => "<input type=\"hidden\" name=\"_method\" value=\"{$method}\">");
        }

        $blade->directive('csrf', fn () => $this->getCsrfField());
    }

    private function _registerPermissionDirectives(Blade $blade): void
    {
        $blade->directive('can', fn ($expression) => "<?php if(\$this->checkPermission($expression)): ?>");
        $blade->directive('endcan', fn () => '<?php endif; ?>');
        $blade->directive('cannot', fn ($expression) => "<?php if(!\$this->checkPermission($expression)): ?>");
        $blade->directive('endcannot', fn () => '<?php endif; ?>');
    }

    private function _registerAuthDirectives(Blade $blade): void
    {
        $blade->directive('auth', fn () => '<?php if($this->isAuthenticated()):?>');
        $blade->directive('endauth', fn () => '<?php endif;?>');
        $blade->directive('guest', fn () => '<?php if(!$this->isAuthenticated()):?>');
        $blade->directive('endguest', fn () => '<?php endif;?>');
    }

    private function _registerErrorDirectives(Blade $blade): void
    {
        $blade->directive('error', function ($expression) {
            return "<?php
                \$__fieldName = {$expression};
                \$__bladeErrors = \$errors ?? null;
                if (\$__bladeErrors && \$__bladeErrors->has(\$__fieldName)):
                \$message = \$__bladeErrors->first(\$__fieldName);
            ?>";
        });
        $blade->directive('enderror', fn () => '<?php unset($message, $__fieldName, $__bladeErrors); endif; ?>');
    }

    private function _registerLangDirectives(Blade $blade): void
    {
        $blade->directive('lang', function ($expression) {
            return "<?php echo \$this->translate({$expression});?>";
        });
    }

    private function _registerFragmentDirectives(Blade $blade): void
    {
        $blade->directive('fragment', function ($expression) {
            $fragment = trim($expression, "()\"'");
            return "<!-- fragment: {$fragment} -->";
        });

        $blade->directive('endfragment', function ($expression) {
            $fragment = trim($expression, "()\"'");
            return "<!-- endfragment: {$fragment} -->";
        });
    }

    private function _registerUtilityDirectives(Blade $blade): void
    {
        $blade->directive('asset', function ($expression) {
            return "<?php echo \$this->asset({$expression}); ?>";
        });

        $blade->directive('url', function ($expression) {
            return "<?php echo \$this->url({$expression}); ?>";
        });

        $blade->directive('route', function ($expression) {
            return "<?php echo \$this->route({$expression}); ?>";
        });

        $blade->directive('config', function ($expression) {
            return "<?php echo \$this->config({$expression}); ?>";
        });
    }

    private function _registerConditionalDirectives(Blade $blade): void
    {
        $blade->directive('production', fn () => '<?php if($this->isProduction()): ?>');
        $blade->directive('endproduction', fn () => '<?php endif; ?>');
        
        $blade->directive('development', fn () => '<?php if($this->isDevelopment()): ?>');
        $blade->directive('enddevelopment', fn () => '<?php endif; ?>');
        
        $blade->directive('env', function ($expression) {
            return "<?php if(\$this->checkEnvironment({$expression})): ?>";
        });
        $blade->directive('endenv', fn () => '<?php endif; ?>');
    }

    protected function getCsrfField(): string
    {
        // Generate or retrieve CSRF token - override in subclass for specific implementation
        $token = $this->getCsrfToken();
        return "<input type=\"hidden\" name=\"_token\" value=\"{$token}\">";
    }

    protected function getCsrfToken(): string
    {
        // Default implementation - override for your security needs
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['_token'])) {
                $_SESSION['_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['_token'];
        }
        
        return '';
    }
}