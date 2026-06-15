<?php

namespace Emreyilmaz99\SanalPos\Testing;

use Emreyilmaz99\SanalPos\Contracts\Capabilities\SupportsHostedPayment;
use Emreyilmaz99\SanalPos\Contracts\Capabilities\SupportsRefund;
use Emreyilmaz99\SanalPos\Contracts\Capabilities\SupportsTokenization;
use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\AdditionalInstallmentQueryRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\AllInstallmentQueryRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\BINInstallmentQueryRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\CancelRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\ChargeStoredCardRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\DeleteStoredCardRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\RefundRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\Sale3DResponse;
use Emreyilmaz99\SanalPos\DTOs\Requests\SaleQueryRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\SaleRequest;
use Emreyilmaz99\SanalPos\DTOs\Requests\StoreCardRequest;
use Emreyilmaz99\SanalPos\DTOs\Responses\AdditionalInstallmentQueryResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\AllInstallmentQueryResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\BINInstallmentQueryResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\CancelResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\DeleteStoredCardResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\HostedPaymentResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\RefundResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\SaleQueryResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\SaleResponse;
use Emreyilmaz99\SanalPos\DTOs\Responses\StoreCardResponse;
use Emreyilmaz99\SanalPos\Gateways\AbstractGateway;

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
