<?php

namespace Emreyilmaz99\SanalPos\Support\Retry;

class InMemoryCircuitBreakerStore implements CircuitBreakerStore
{
    /** @var array<string, int> */
    private array $failures = [];

    /** @var array<string, int> openUntil timestamp */
    private array $openUntil = [];

    public function failures(string $host): int
    {
        return $this->failures[$host] ?? 0;
    }

    public function incrementFailures(string $host, int $ttlSeconds): int
    {
        return $this->failures[$host] = ($this->failures[$host] ?? 0) + 1;
    }

    public function reset(string $host): void
    {
        unset($this->failures[$host], $this->openUntil[$host]);
    }

    public function open(string $host, int $seconds): void
    {
        $this->openUntil[$host] = time() + $seconds;
    }

    public function openedFor(string $host): int
    {
        $until = $this->openUntil[$host] ?? 0;
        $remaining = $until - time();

        return $remaining > 0 ? $remaining : 0;
    }
}
