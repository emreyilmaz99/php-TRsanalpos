<?php

namespace EvrenOnur\SanalPos\DTOs\Responses;

use EvrenOnur\SanalPos\Enums\ResponseStatus;

/**
 * Hosted ödeme başlatma yanıtı.
 *
 * İki tür akışı destekler:
 *   1. REDIRECT (GET) — örn. Iyzico CheckoutForm: bankanın döndüğü tek bir URL
 *      vardır, kullanıcı doğrudan oraya yönlendirilir. redirect_url doldurulur.
 *   2. FORM_POST — örn. NestPay/Akbank 3D_PAY_HOSTING: bir gateway URL'sine
 *      hidden field'larla form POST atılır. redirect_url + form_fields doldurulur.
 *
 * Yöntemin türünü redirect_method belirler. form_fields boş ise GET redirect demektir.
 */
class HostedPaymentResponse
{
    public function __construct(
        public ResponseStatus $status = ResponseStatus::Error,
        public string $message = '',
        public string $order_number = '',
        public string $redirect_method = 'GET',
        public string $redirect_url = '',
        public array $form_fields = [],
        public ?string $token = null,
        public ?array $private_response = null,
    ) {}
}
