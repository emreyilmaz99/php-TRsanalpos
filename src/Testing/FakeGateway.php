<?php

namespace EvrenOnur\SanalPos\Testing;

use EvrenOnur\SanalPos\Contracts\Capabilities\SupportsHostedPayment;
use EvrenOnur\SanalPos\Contracts\Capabilities\SupportsRefund;
use EvrenOnur\SanalPos\Contracts\Capabilities\SupportsTokenization;
use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\AdditionalInstallmentQueryRequest;
use EvrenOnur\SanalPos\DTOs\Requests\AllInstallmentQueryRequest;
use EvrenOnur\SanalPos\DTOs\Requests\BINInstallmentQueryRequest;
use EvrenOnur\SanalPos\DTOs\Requests\CancelRequest;
use EvrenOnur\SanalPos\DTOs\Requests\ChargeStoredCardRequest;
use EvrenOnur\SanalPos\DTOs\Requests\DeleteStoredCardRequest;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\Requests\RefundRequest;
use EvrenOnur\SanalPos\DTOs\Requests\Sale3DResponse;
use EvrenOnur\SanalPos\DTOs\Requests\SaleQueryRequest;
use EvrenOnur\SanalPos\DTOs\Requests\SaleRequest;
use EvrenOnur\SanalPos\DTOs\Requests\StoreCardRequest;
use EvrenOnur\SanalPos\DTOs\Responses\AdditionalInstallmentQueryResponse;
use EvrenOnur\SanalPos\DTOs\Responses\AllInstallmentQueryResponse;
use EvrenOnur\SanalPos\DTOs\Responses\BINInstallmentQueryResponse;
use EvrenOnur\SanalPos\DTOs\Responses\CancelResponse;
use EvrenOnur\SanalPos\DTOs\Responses\DeleteStoredCardResponse;
use EvrenOnur\SanalPos\DTOs\Responses\HostedPaymentResponse;
use EvrenOnur\SanalPos\DTOs\Responses\RefundResponse;
use EvrenOnur\SanalPos\DTOs\Responses\SaleQueryResponse;
use EvrenOnur\SanalPos\DTOs\Responses\SaleResponse;
use EvrenOnur\SanalPos\DTOs\Responses\StoreCardResponse;
use EvrenOnur\SanalPos\Gateways\AbstractGateway;

/**
 * SanalPos::fake() etkinleştirildiğinde tüm banka çağrıları bu gateway'e gider.
 * HTTP yapmaz; FakePos yöneticisine delegasyon yapar.
 *
 * Tüm capability marker'ları implement eder — runtime'da `instanceof` kontrolleri
 * geçer (uygulamanın "destekliyor mu?" branch'leri test edilebilsin diye).
 */
class FakeGateway extends AbstractGateway implements SupportsHostedPayment, SupportsRefund, SupportsTokenization
{
    public function sale(SaleRequest $request, MerchantAuth $auth): SaleResponse
    {
        /** @var SaleResponse */
        return FakePos::instance()->dispatch('sale', $request, $auth);
    }

    public function sale3DResponse(Sale3DResponse $request, MerchantAuth $auth): SaleResponse
    {
        /** @var SaleResponse */
        return FakePos::instance()->dispatch('sale3DResponse', $request, $auth);
    }

    public function binInstallmentQuery(BINInstallmentQueryRequest $request, MerchantAuth $auth): BINInstallmentQueryResponse
    {
        /** @var BINInstallmentQueryResponse */
        return FakePos::instance()->dispatch('binInstallmentQuery', $request, $auth);
    }

    public function allInstallmentQuery(AllInstallmentQueryRequest $request, MerchantAuth $auth): AllInstallmentQueryResponse
    {
        /** @var AllInstallmentQueryResponse */
        return FakePos::instance()->dispatch('allInstallmentQuery', $request, $auth);
    }

    public function additionalInstallmentQuery(AdditionalInstallmentQueryRequest $request, MerchantAuth $auth): AdditionalInstallmentQueryResponse
    {
        /** @var AdditionalInstallmentQueryResponse */
        return FakePos::instance()->dispatch('additionalInstallmentQuery', $request, $auth);
    }

    public function cancel(CancelRequest $request, MerchantAuth $auth): CancelResponse
    {
        /** @var CancelResponse */
        return FakePos::instance()->dispatch('cancel', $request, $auth);
    }

    public function refund(RefundRequest $request, MerchantAuth $auth): RefundResponse
    {
        /** @var RefundResponse */
        return FakePos::instance()->dispatch('refund', $request, $auth);
    }

    public function saleQuery(SaleQueryRequest $request, MerchantAuth $auth): SaleQueryResponse
    {
        /** @var SaleQueryResponse */
        return FakePos::instance()->dispatch('saleQuery', $request, $auth);
    }

    public function initializeHostedPayment(HostedPaymentRequest $request, MerchantAuth $auth): HostedPaymentResponse
    {
        /** @var HostedPaymentResponse */
        return FakePos::instance()->dispatch('initializeHostedPayment', $request, $auth);
    }

    public function resolveHostedPayment(HostedPaymentCallback $callback, MerchantAuth $auth): SaleResponse
    {
        /** @var SaleResponse */
        return FakePos::instance()->dispatch('resolveHostedPayment', $callback, $auth);
    }

    public function storeCard(StoreCardRequest $request, MerchantAuth $auth): StoreCardResponse
    {
        /** @var StoreCardResponse */
        return FakePos::instance()->dispatch('storeCard', $request, $auth);
    }

    public function chargeStoredCard(ChargeStoredCardRequest $request, MerchantAuth $auth): SaleResponse
    {
        /** @var SaleResponse */
        return FakePos::instance()->dispatch('chargeStoredCard', $request, $auth);
    }

    public function deleteStoredCard(DeleteStoredCardRequest $request, MerchantAuth $auth): DeleteStoredCardResponse
    {
        /** @var DeleteStoredCardResponse */
        return FakePos::instance()->dispatch('deleteStoredCard', $request, $auth);
    }
}
