<?php

namespace EvrenOnur\SanalPos\Infrastructure\Iyzico\Request;

use EvrenOnur\SanalPos\Infrastructure\Iyzico\Model\IyzicoAddress;
use EvrenOnur\SanalPos\Infrastructure\Iyzico\Model\IyzicoBasketItem;
use EvrenOnur\SanalPos\Infrastructure\Iyzico\Model\IyzicoBuyer;
use EvrenOnur\SanalPos\Infrastructure\Iyzico\PKIRequestStringBuilder;

/**
 * Iyzico CheckoutForm (hosted) ödeme başlatma isteği.
 *
 * CreatePaymentRequest'in kart bilgisi içermeyen versiyonudur. Kart bilgisi,
 * Iyzico'nun barındırdığı ödeme sayfasında kullanıcı tarafından girilir.
 *
 * Endpoint: POST /payment/iyzipos/checkoutform/initialize/auth/ecom
 */
class CreateCheckoutFormInitializeRequest extends IyzicoBaseRequest
{
    public ?string $price = null;

    public ?string $paidPrice = null;

    public ?string $basketId = null;

    public ?string $paymentGroup = null;

    public ?IyzicoBuyer $buyer = null;

    public ?IyzicoAddress $shippingAddress = null;

    public ?IyzicoAddress $billingAddress = null;

    /** @var IyzicoBasketItem[]|null */
    public ?array $basketItems = null;

    public ?string $callbackUrl = null;

    public ?string $currency = null;

    /** @var int[]|null */
    public ?array $enabledInstallments = null;

    public function toPKIRequestString(): string
    {
        return PKIRequestStringBuilder::create()
            ->appendSuper(parent::toPKIRequestString())
            ->appendPrice('price', $this->price)
            ->appendPrice('paidPrice', $this->paidPrice)
            ->append('basketId', $this->basketId)
            ->append('paymentGroup', $this->paymentGroup)
            ->append('buyer', $this->buyer)
            ->append('shippingAddress', $this->shippingAddress)
            ->append('billingAddress', $this->billingAddress)
            ->appendList('basketItems', $this->basketItems)
            ->append('callbackUrl', $this->callbackUrl)
            ->append('currency', $this->currency)
            ->appendList('enabledInstallments', $this->enabledInstallments)
            ->getRequestString();
    }
}
