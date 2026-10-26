<?php

declare(strict_types=1);

namespace MG\Core;

use MG\Exceptions\AppException;

/**
 * Container - minimal servis konteyneri (dependency injection).
 * Singleton hizmetler: PDO, Router, Security servisleri.
 */
final class Container
{
    /** @var array<string, callable(self): object> */
    private array $factories = [];
    /** @var array<string, object> */
    private array $instances = [];

    public function set(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }
        if (!isset($this->factories[$id])) {
            throw new AppException("Servis bulunamadı: {$id}", 500);
        }
        return $this->instances[$id] = ($this->factories[$id])($this);
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]) || isset($this->instances[$id]);
    }
}
