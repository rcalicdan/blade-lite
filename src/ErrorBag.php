<?php

namespace Rcalicdan\Ci4Larabridge\Blade;

/**
 * Framework-agnostic ErrorBag for validation errors in Blade templates
 */
class ErrorBag implements \Serializable
{
    protected array $errors;

    public function __construct(array $errors = [])
    {
        $this->errors = $errors;
    }

    public function has(string $key): bool
    {
        return isset($this->errors[$key]);
    }

    public function first(string $key): ?string
    {
        return $this->errors[$key] ?? null;
    }

    public function getBag(string $key = 'default'): array
    {
        return $this->errors;
    }

    public function any(): bool
    {
        return !empty($this->errors);
    }

    public function all(): array
    {
        return $this->errors;
    }

    public function serialize(): string
    {
        return serialize($this->errors);
    }

    public function unserialize($data): void
    {
        $this->errors = unserialize($data);
    }

    public function __serialize(): array
    {
        return $this->errors;
    }

    public function __unserialize(array $data): void
    {
        $this->errors = $data;
    }
}