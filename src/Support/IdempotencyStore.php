<?php

namespace Emreyilmaz99\SanalPos\Support;

/**
 * Idempotency store kontratı.
 *
 * Sale/initializeHostedPayment çağrılarında aynı `idempotency_key`'in kısa süre
 * içinde tekrar gelmesi durumunda banka'ya ikinci istek yapılmaması için kullanılır.
 *
 * Implementasyonlar:
 *  - LaravelCacheIdempotencyStore (`cache()` helper'ı varsa otomatik bağlanır)
 *  - InMemoryIdempotencyStore (test/CLI için)
 *  - Kendi store'unuzu yazıp SanalPos::setIdempotencyStore() ile inject edebilirsiniz
 */
interface IdempotencyStore
{
    /**
     * Anahtar daha önce kullanıldıysa true döner. Kullanılmadıysa **atomik olarak**
     * işaretlenir ve false döner — "first-time" caller serbestçe ilerler.
     */
    public function seen(string $key, int $ttlSeconds = 3600): bool;
}
