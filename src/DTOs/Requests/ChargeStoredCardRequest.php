<?php

namespace Emreyilmaz99\SanalPos\DTOs\Requests;

use Emreyilmaz99\SanalPos\DTOs\CustomerInfo;
use Emreyilmaz99\SanalPos\DTOs\Payment3DConfig;
use Emreyilmaz99\SanalPos\Enums\Currency;

/**
 * Saklanmış kart token'ı ile çekim. Bankaya kart numarası gönderilmez.
 *
 * NOT: Banka kuralları gereği MIT (Merchant-Initiated Transaction) için bile
 * ilk çekim CIT (Customer-Initiated, 3DS) olmalıdır. Sonraki recurring/MIT
 * çekimler için 3D zorunlu değil — `payment_3d` null bırakılabilir.
 */
class ChargeStoredCardRequest
{
    public function __construct(
        public string $order_number,
        public string $customer_id,
        public string $card_token,
        public float $amount,
        public Currency $currency = Currency::TRY,
        public int $installment = 0,
        public string $customer_ip_address = '',
        public ?string $card_user_key = null,
        public ?int $cvv = null,
        public ?CustomerInfo $invoice_info = null,
        public ?Payment3DConfig $payment_3d = null,
        public ?string $idempotency_key = null,
    ) {}

    /**
     * @return list<string>
     */
    public function validate(): array
    {
        $errors = [];
        if ($this->order_number === '') {
            $errors[] = 'order_number zorunlu.';
        }
        if ($this->customer_id === '') {
            $errors[] = 'customer_id zorunlu.';
        }
        if ($this->card_token === '') {
            $errors[] = 'card_token zorunlu.';
        }
        if ($this->amount <= 0) {
            $errors[] = 'amount 0\'dan büyük olmalı.';
        }

        return $errors;
    }
}
