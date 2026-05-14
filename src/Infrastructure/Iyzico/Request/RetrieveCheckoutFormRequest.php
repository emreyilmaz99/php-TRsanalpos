<?php

namespace EvrenOnur\SanalPos\Infrastructure\Iyzico\Request;

use EvrenOnur\SanalPos\Infrastructure\Iyzico\PKIRequestStringBuilder;

/**
 * Iyzico CheckoutForm sonuç sorgulama isteği.
 *
 * Hosted ödeme sonrası bankadan dönen `token` ile çağrılır; ödeme detayını
 * (paymentId, paymentStatus vb.) döner.
 *
 * Endpoint: POST /payment/iyzipos/checkoutform/auth/ecom/detail
 */
class RetrieveCheckoutFormRequest extends IyzicoBaseRequest
{
    public ?string $token = null;

    public function toPKIRequestString(): string
    {
        return PKIRequestStringBuilder::create()
            ->appendSuper(parent::toPKIRequestString())
            ->append('token', $this->token)
            ->getRequestString();
    }
}
