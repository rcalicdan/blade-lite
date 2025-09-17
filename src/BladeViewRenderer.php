<?php

namespace Rcalicdan\BladeLite;

/**
 * Blade View Renderer with zero-setup initialization
 */
class BladeViewRenderer
{
    protected BladeService $blade;
    protected ?string $view = null;
    protected array $data = [];
    protected array $fragments = [];

    public function __construct(?BladeService $blade = null)
    {
        $this->blade = $blade ?? BladeService::getInstance();
    }

    public function view(string $view): self
    {
        $this->view = $view;
        return $this;
    }

    public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function fragment($fragments): self
    {
        $this->fragments = is_array($fragments) ? $fragments : [$fragments];
        return $this;
    }

    public function fragmentIf(bool $condition, $fragments, $fallback = null): self
    {
        if ($condition) {
            $this->fragments = is_array($fragments) ? $fragments : [$fragments];
        } elseif ($fallback !== null) {
            $this->fragments = is_array($fallback) ? $fallback : [$fallback];
        }

        return $this;
    }

    public function render(): string
    {
        if (empty($this->view)) {
            throw new \InvalidArgumentException('No view has been specified');
        }

        if (!empty($this->fragments)) {
            $this->data['__fragments'] = $this->fragments;
        }

        $output = $this->blade->render($this->view, $this->data);

        if (!empty($this->fragments)) {
            $output = $this->extractFragments($output, $this->fragments);
        }

        // Reset state for next render
        $this->data = [];
        $this->fragments = [];

        return $output;
    }

    protected function extractFragments(string $output, array $fragments): string
    {
        $extractedContent = '';

        foreach ($fragments as $fragment) {
            $pattern = '/<!--\s*fragment\s*:\s*' . preg_quote($fragment, '/') . '\s*-->(.*?)<!--\s*endfragment\s*:\s*' . preg_quote($fragment, '/') . '\s*-->/s';

            if (preg_match($pattern, $output, $matches)) {
                $extractedContent .= trim($matches[1]);
            }
        }

        return $extractedContent ?: $output;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}