<?php

namespace Emreyilmaz99\SanalPos\Infrastructure\Iyzico\Request;

use Emreyilmaz99\SanalPos\Infrastructure\Iyzico\PKIRequestStringBuilder;

/**
 * Iyzico 3DS ödeme tamamlama isteği.
 */
class CreateThreedsPaymentRequest extends IyzicoBaseRequest
{
    public ?string $paymentId = null;

    public ?string $conversationData = null;

    public function toPKIRequestString(): string
    {
        return PKIRequestStringBuilder::create()
            ->appendSuper(parent::toPKIRequestString())
            ->append('paymentId', $this->paymentId)
            ->append('conversationData', $this->conversationData)
            ->getRequestString();
    }
}
