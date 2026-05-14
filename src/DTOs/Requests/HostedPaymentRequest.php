<?php

namespace EvrenOnur\SanalPos\DTOs\Requests;

use EvrenOnur\SanalPos\DTOs\CustomerInfo;
use EvrenOnur\SanalPos\DTOs\SaleInfo;

/**
 * Hosted (banka tarafında kart girişi) ödeme başlatma isteği.
 *
 * Kart bilgisi taşımaz; tutar, sipariş ve dönüş URL'leri ile banka tarafından
 * barındırılan ödeme sayfası için bir yönlendirme/form üretilir.
 */
class HostedPaymentRequest
{
    public function __construct(
        public string $order_number = '',
        public string $customer_ip_address = '',
        public ?SaleInfo $sale_info = null,
        public ?CustomerInfo $invoice_info = null,
        public ?CustomerInfo $shipping_info = null,
        public string $success_url = '',
        public string $fail_url = '',
        public string $language = 'tr',
        public bool $is_desktop = true,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            order_number: $data['order_number'] ?? '',
            customer_ip_address: $data['customer_ip_address'] ?? '',
            sale_info: isset($data['sale_info']) ? SaleInfo::fromArray($data['sale_info']) : null,
            invoice_info: isset($data['invoice_info']) ? CustomerInfo::fromArray($data['invoice_info']) : null,
            shipping_info: isset($data['shipping_info']) ? CustomerInfo::fromArray($data['shipping_info']) : null,
            success_url: $data['success_url'] ?? '',
            fail_url: $data['fail_url'] ?? '',
            language: $data['language'] ?? 'tr',
            is_desktop: (bool) ($data['is_desktop'] ?? true),
        );
    }

    public function validate(): array
    {
        $errors = [];

        if (empty($this->order_number)) {
            $errors[] = 'Sipariş numarası boş olamaz.';
        }

        if (empty($this->success_url)) {
            $errors[] = 'Başarı URL\'i boş olamaz.';
        }

        if (empty($this->fail_url)) {
            $errors[] = 'Hata URL\'i boş olamaz.';
        }

        if ($this->sale_info === null) {
            $errors[] = 'Satış bilgileri (tutar, currency, installment) boş olamaz.';
        } else {
            if ($this->sale_info->amount <= 0) {
                $errors[] = 'Tutar sıfırdan büyük olmalıdır.';
            }
            if ($this->sale_info->installment < 1 || $this->sale_info->installment > 15) {
                $errors[] = 'Taksit sayısı 1-15 arasında olmalıdır.';
            }
        }

        return $errors;
    }
}
