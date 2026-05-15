<?php

namespace EvrenOnur\SanalPos\Support;

/**
 * Laravel `Cache` facade'ı üzerinden çalışan idempotency store.
 * `Cache::add()` atomicliği "first-write wins" sağlar — race condition'da güvenli.
 *
 * Laravel container yoksa kullanıma alınmaz; SanalPos::idempotencyStore() varsayılan
 * olarak Laravel varsa bunu, yoksa InMemoryIdempotencyStore'u döner.
 */
class LaravelCacheIdempotencyStore implements IdempotencyStore
{
    private string $prefix;

    public function __construct(string $prefix = 'sanalpos:idem:')
    {
        $this->prefix = $prefix;
    }

    public function seen(string $key, int $ttlSeconds = 3600): bool
    {
        if (! function_exists('cache')) {
            return false; // Laravel yok — idempotency atlanır
        }

        $fullKey = $this->prefix . $key;

        // Cache::add() — anahtar yoksa ekler ve true, varsa false döner. Atomic.
        try {
            /** @phpstan-ignore-next-line */
            return ! cache()->add($fullKey, '1', $ttlSeconds);
        } catch (\Throwable) {
            return false; // Cache başarısızsa idempotency yok — yine de istek gönder.
        }
    }
}
