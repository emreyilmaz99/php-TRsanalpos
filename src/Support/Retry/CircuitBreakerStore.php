<?php

namespace EvrenOnur\SanalPos\Support\Retry;

/**
 * Circuit breaker state storage. Host (örn. "api.akbank.com") başına
 * failure sayacı ve open-until timestamp tutar.
 */
interface CircuitBreakerStore
{
    /** Mevcut ardışık fail sayısı. */
    public function failures(string $host): int;

    /** Failure sayacını 1 artırır ve yeni değeri döner. */
    public function incrementFailures(string $host, int $ttlSeconds): int;

    /** Başarılı çağrı sonrası tüm state'i temizler. */
    public function reset(string $host): void;

    /** Devreyi $seconds süreliğine kapatır (open). */
    public function open(string $host, int $seconds): void;

    /**
     * Devre şu an açıksa kalan saniye, değilse 0.
     */
    public function openedFor(string $host): int;
}
