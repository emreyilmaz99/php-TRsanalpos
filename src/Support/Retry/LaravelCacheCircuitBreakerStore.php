<?php

namespace EvrenOnur\SanalPos\Support\Retry;

/**
 * Laravel cache backed circuit breaker store. Birden fazla process arasında
 * paylaşımlı state için (queue worker'lar, web request'ler).
 */
class LaravelCacheCircuitBreakerStore implements CircuitBreakerStore
{
    public function __construct(
        private readonly string $prefix = 'sanalpos:cb:',
    ) {}

    public function failures(string $host): int
    {
        if (! function_exists('cache')) {
            return 0;
        }

        try {
            /** @phpstan-ignore-next-line */
            return (int) cache()->get($this->prefix . 'fails:' . $host, 0);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function incrementFailures(string $host, int $ttlSeconds): int
    {
        if (! function_exists('cache')) {
            return 0;
        }

        $key = $this->prefix . 'fails:' . $host;

        try {
            /** @phpstan-ignore-next-line */
            $store = cache();
            $current = (int) $store->get($key, 0);
            $new = $current + 1;
            $store->put($key, $new, $ttlSeconds);

            return $new;
        } catch (\Throwable) {
            return 0;
        }
    }

    public function reset(string $host): void
    {
        if (! function_exists('cache')) {
            return;
        }

        try {
            /** @phpstan-ignore-next-line */
            $store = cache();
            $store->forget($this->prefix . 'fails:' . $host);
            $store->forget($this->prefix . 'open:' . $host);
        } catch (\Throwable) {
            // sessiz
        }
    }

    public function open(string $host, int $seconds): void
    {
        if (! function_exists('cache')) {
            return;
        }

        try {
            /** @phpstan-ignore-next-line */
            cache()->put($this->prefix . 'open:' . $host, time() + $seconds, $seconds);
        } catch (\Throwable) {
            // sessiz
        }
    }

    public function openedFor(string $host): int
    {
        if (! function_exists('cache')) {
            return 0;
        }

        try {
            /** @phpstan-ignore-next-line */
            $until = (int) cache()->get($this->prefix . 'open:' . $host, 0);
        } catch (\Throwable) {
            return 0;
        }

        $remaining = $until - time();

        return $remaining > 0 ? $remaining : 0;
    }
}
