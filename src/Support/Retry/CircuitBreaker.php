<?php

namespace EvrenOnur\SanalPos\Support\Retry;

use EvrenOnur\SanalPos\Exceptions\CircuitOpenException;

/**
 * Basit failure-count circuit breaker. Host başına ardışık fail sayısı
 * eşiği aşınca devre `openSeconds` süreyle açılır; bu süre içinde tüm
 * çağrılar `CircuitOpenException` ile reddedilir. Başarılı çağrı sayacı sıfırlar.
 *
 * Half-open state implement edilmedi — `openSeconds` dolunca otomatik
 * "denemeye açık" hâle gelir; ilk denemenin sonucu yeni state'i belirler.
 */
class CircuitBreaker
{
    public function __construct(
        public readonly CircuitBreakerStore $store,
        public readonly int $failureThreshold = 5,
        public readonly int $openSeconds = 30,
        public readonly int $failureCounterTtl = 60,
    ) {}

    /**
     * İstek öncesi çağır — devre kapalıysa istisna fırlatır.
     *
     * @throws CircuitOpenException
     */
    public function guard(string $host): void
    {
        $openedFor = $this->store->openedFor($host);
        if ($openedFor > 0) {
            throw new CircuitOpenException($host, $openedFor);
        }
    }

    /**
     * Başarılı çağrı — sayaçları sıfırla.
     */
    public function recordSuccess(string $host): void
    {
        $this->store->reset($host);
    }

    /**
     * Başarısız çağrı — eşiği aşarsa devreyi aç.
     */
    public function recordFailure(string $host): void
    {
        $fails = $this->store->incrementFailures($host, $this->failureCounterTtl);
        if ($fails >= $this->failureThreshold) {
            $this->store->open($host, $this->openSeconds);
        }
    }
}
