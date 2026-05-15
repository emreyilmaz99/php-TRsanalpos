<?php

namespace EvrenOnur\SanalPos\Events;

use EvrenOnur\SanalPos\DTOs\MerchantAuth;

/**
 * Tüm payment event'leri için ortak temel sınıf.
 *
 * Bu kütüphane Laravel'in event dispatcher'ı (Event facade) varsa onu kullanır;
 * yoksa event sessizce no-op'tur. Çağrı stilinin Laravel ekosistemi dışında da
 * çalışabilmesi için generic — Symfony/PSR-14 dispatcher ile entegrasyon kullanıcıya
 * bırakılır (SanalPos::onEvent(callable) ile listener ekleyebilirler).
 */
abstract class PaymentEvent
{
    public function __construct(
        public readonly string $bankCode,
        public readonly string $orderNumber,
        public readonly ?MerchantAuth $auth = null,
        public readonly array $context = [],
    ) {}

    /**
     * Event tipini insan-okunabilir string olarak döner. Log'a yazılır.
     */
    public function name(): string
    {
        return static::class;
    }
}
