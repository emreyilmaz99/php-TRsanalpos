<?php

namespace EvrenOnur\SanalPos\DTOs\Requests;

use EvrenOnur\SanalPos\DTOs\CustomerInfo;

/**
 * Karta token üretmek için (kart saklama). Sonraki çekimlerde kart numarası
 * yerine bu token gönderilir — PCI-DSS sahasını daraltır.
 *
 * Üretilen token bankaya/gateway'e özgüdür; farklı gateway'de geçerli değildir.
 */
class StoreCardRequest
{
    public function __construct(
        public string $customer_id,
        public string $card_number,
        public string $expiry_month,
        public string $expiry_year,
        public string $card_holder_name = '',
        public ?string $cvv = null,
        public ?string $card_alias = null,
        public ?CustomerInfo $customer = null,
        public ?string $idempotency_key = null,
    ) {}

    /**
     * @return list<string> hatalar — boş ise geçerli
     */
    public function validate(): array
    {
        $errors = [];
        if ($this->customer_id === '') {
            $errors[] = 'customer_id zorunlu.';
        }
        if (! preg_match('/^\d{12,19}$/', $this->card_number)) {
            $errors[] = 'card_number 12-19 hane olmalı.';
        }
        if (! preg_match('/^\d{2}$/', $this->expiry_month) || (int) $this->expiry_month < 1 || (int) $this->expiry_month > 12) {
            $errors[] = 'expiry_month 01-12 aralığında 2 hane olmalı.';
        }
        if (! preg_match('/^\d{2,4}$/', $this->expiry_year)) {
            $errors[] = 'expiry_year 2 veya 4 hane olmalı.';
        }

        return $errors;
    }
}
