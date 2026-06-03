<?php

namespace EvrenOnur\SanalPos\Support\Retry;

/**
 * HTTP retry politikası — sadece **idempotent** çağrılarda kullanılmalıdır
 * (saleQuery, installmentQuery vb.). Sale/refund gibi mutating çağrılar için
 * default'ta kapalıdır; yanlışlıkla iki kez tahsilat olmasın diye.
 *
 * Exponential backoff: delay = min(maxDelayMs, baseDelayMs * 2^(attempt-1)) + jitter.
 */
class RetryPolicy
{
    /**
     * @param  int[]  $retryOnStatus  Bu HTTP status kodları için retry uygulanır
     */
    public function __construct(
        public readonly int $maxAttempts = 3,
        public readonly int $baseDelayMs = 200,
        public readonly int $maxDelayMs = 2000,
        public readonly array $retryOnStatus = [408, 429, 502, 503, 504],
        public readonly bool $retryOnNetworkError = true,
    ) {}

    /**
     * Bu attempt için ne kadar bekleyelim (ms). 1 → baseDelay, 2 → 2x, vs.
     */
    public function delayMs(int $attempt): int
    {
        $exp = $this->baseDelayMs * (2 ** max(0, $attempt - 1));
        $bounded = min($this->maxDelayMs, $exp);
        // %25'e kadar jitter — thundering herd önlemi
        $jitter = random_int(0, max(1, (int) ($bounded * 0.25)));

        return $bounded + $jitter;
    }

    /**
     * Tek deneme — retry kapalı.
     */
    public static function none(): self
    {
        return new self(maxAttempts: 1);
    }
}
