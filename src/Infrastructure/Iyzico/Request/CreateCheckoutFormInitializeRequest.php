<?php

namespace EvrenOnur\SanalPos\Infrastructure\Iyzico\Request;

use EvrenOnur\SanalPos\Infrastructure\Iyzico\Model\IyzicoAddress;
use EvrenOnur\SanalPos\Infrastructure\Iyzico\Model\IyzicoBasketItem;
use EvrenOnur\SanalPos\Infrastructure\Iyzico\Model\IyzicoBuyer;
use EvrenOnur\SanalPos\Infrastructure\Iyzico\PKIRequestStringBuilder;

/**
 * Iyzico CheckoutForm (hosted) ödeme başlatma isteği.
 *
 * Property DECLARATION order'ı KRİTİK — Iyzico v2 hash server tarafında order-sensitive
 * olarak doğrulanıyor. Sıra resmi iyzipay-php SDK'sındaki getJsonObject() / toPKIRequestString()
 * `add(...)` çağrı sırasıyla **birebir** aynı olmalı.
 *
 * SDK referans sıralaması:
 *   locale, conversationId (parent)
 *   price, basketId, paymentGroup, buyer, shippingAddress, billingAddress,
 *   basketItems, callbackUrl, paymentSource, currency, posOrderId, paidPrice,
 *   forceThreeDS, cardUserKey, enabledInstallments, debitCardAllowed, shippingAmountExcluded
 *
 * Endpoint: POST /payment/iyzipos/checkoutform/initialize/auth/ecom
 */
class CreateCheckoutFormInitializeRequest extends IyzicoBaseRequest
{
    public ?string $price = null;

    public ?string $basketId = null;

    public ?string $paymentGroup = null;

    public ?IyzicoBuyer $buyer = null;

    public ?IyzicoAddress $shippingAddress = null;

    public ?IyzicoAddress $billingAddress = null;

    /** @var IyzicoBasketItem[]|null */
    public ?array $basketItems = null;

    public ?string $callbackUrl = null;

    public ?string $currency = null;

    public ?string $paidPrice = null;

    /** @var int[]|null */
    public ?array $enabledInstallments = null;

    public function toPKIRequestString(): string
    {
        return PKIRequestStringBuilder::create()
            ->appendSuper(parent::toPKIRequestString())
            ->appendPrice('price', $this->price)
            ->append('basketId', $this->basketId)
            ->append('paymentGroup', $this->paymentGroup)
            ->append('buyer', $this->buyer)
            ->append('shippingAddress', $this->shippingAddress)
            ->append('billingAddress', $this->billingAddress)
            ->appendList('basketItems', $this->basketItems)
            ->append('callbackUrl', $this->callbackUrl)
            ->append('currency', $this->currency)
            ->appendPrice('paidPrice', $this->paidPrice)
            ->appendList('enabledInstallments', $this->enabledInstallments)
            ->getRequestString();
    }
}
