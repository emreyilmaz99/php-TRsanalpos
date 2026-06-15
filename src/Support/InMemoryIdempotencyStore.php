<?php

namespace Emreyilmaz99\SanalPos\Support;

/**
 * Süreç-içi idempotency store. Sadece tek bir PHP isteğinin ömrü boyunca yaşar —
 * production'da Laravel cache veya Redis tabanlı store kullanın. Test/CLI için
 * yeterlidir.
 */
class InMemoryIdempotencyStore implements IdempotencyStore
{
    /** @var array<string, int> key → expiresAt */
    private array $store = [];

    public function seen(string $key, int $ttlSeconds = 3600): bool
    {
        $now = time();

        // Süresi geçenleri temizle (lazy)
        if (isset($this->store[$key]) && $this->store[$key] < $now) {
            unset($this->store[$key]);
        }

        if (isset($this->store[$key])) {
            return true;
        }

        $this->store[$key] = $now + $ttlSeconds;

        return false;
    }
}
