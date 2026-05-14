<?php

namespace EvrenOnur\SanalPos\DTOs\Requests;

/**
 * Hosted ödeme sonrası bankadan gelen callback (success/fail URL) verisi.
 *
 * Banka, hosted ödeme sayfasında işlem tamamlandıktan sonra success_url veya fail_url
 * adresine POST/GET ile geri döner. Bu DTO o ham veriyi taşır; gateway'in
 * resolveHostedPayment() metodu doğrulayıp SaleResponse'a çevirir.
 */
class HostedPaymentCallback
{
    public function __construct(
        public string $order_number = '',
        public array $payload = [],
        public ?string $token = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            order_number: $data['order_number'] ?? '',
            payload: $data['payload'] ?? [],
            token: $data['token'] ?? null,
        );
    }
}
