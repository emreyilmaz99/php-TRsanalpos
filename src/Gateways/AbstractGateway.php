<?php

namespace Emreyilmaz99\SanalPos\Gateways;

use Emreyilmaz99\SanalPos\Contracts\VirtualPOSServiceInterface;
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
use Emreyilmaz99\SanalPos\DTOs\Requests\SaleQueryRequest;
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
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Enums\SaleQueryResponseStatus;
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Support\LoggerAware;
use Emreyilmaz99\SanalPos\Support\MakesHttpRequests;

/**
 * Tüm standalone gateway'ler için temel abstract sınıf.
 *
 * Desteklenmeyen işlemler için varsayılan stub yanıtlar sağlar.
 * Gateway'ler destekledikleri işlemleri override ederek gerçek implementasyon sağlar.
 */
abstract class AbstractGateway implements VirtualPOSServiceInterface
{
    use LoggerAware;
    use MakesHttpRequests;

    public function cancel(CancelRequest $request, MerchantAuth $auth): CancelResponse
    {
        return new CancelResponse(status: ResponseStatus::Error, message: 'Bu banka için iptal metodu henüz tanımlanmamış!');
    }

    public function refund(RefundRequest $request, MerchantAuth $auth): RefundResponse
    {
        return new RefundResponse(status: ResponseStatus::Error, message: 'Bu banka için iade metodu henüz tanımlanmamış!');
    }

    public function binInstallmentQuery(BINInstallmentQueryRequest $request, MerchantAuth $auth): BINInstallmentQueryResponse
    {
        return new BINInstallmentQueryResponse(confirm: false);
    }

    public function allInstallmentQuery(AllInstallmentQueryRequest $request, MerchantAuth $auth): AllInstallmentQueryResponse
    {
        return new AllInstallmentQueryResponse(confirm: false);
    }

    public function additionalInstallmentQuery(AdditionalInstallmentQueryRequest $request, MerchantAuth $auth): AdditionalInstallmentQueryResponse
    {
        return new AdditionalInstallmentQueryResponse(confirm: false);
    }

    public function saleQuery(SaleQueryRequest $request, MerchantAuth $auth): SaleQueryResponse
    {
        return new SaleQueryResponse(status: SaleQueryResponseStatus::Error, message: 'Bu sanal pos için satış sorgulama işlemi şuan desteklenmiyor');
    }

    public function initializeHostedPayment(HostedPaymentRequest $request, MerchantAuth $auth): HostedPaymentResponse
    {
        return new HostedPaymentResponse(
            status: ResponseStatus::Error,
            message: 'Bu sanal pos için hosted (banka barındırmalı) ödeme akışı şuan desteklenmiyor.',
            order_number: $request->order_number,
        );
    }

    public function resolveHostedPayment(HostedPaymentCallback $callback, MerchantAuth $auth): SaleResponse
    {
        return new SaleResponse(
            status: SaleResponseStatus::Error,
            message: 'Bu sanal pos için hosted ödeme callback çözümleme şuan desteklenmiyor.',
            order_number: $callback->order_number,
        );
    }

    public function storeCard(StoreCardRequest $request, MerchantAuth $auth): StoreCardResponse
    {
        return new StoreCardResponse(
            status: ResponseStatus::Error,
            message: 'Bu sanal pos için kart saklama (tokenization) şuan desteklenmiyor.',
        );
    }

    public function chargeStoredCard(ChargeStoredCardRequest $request, MerchantAuth $auth): SaleResponse
    {
        return new SaleResponse(
            status: SaleResponseStatus::Error,
            message: 'Bu sanal pos için saklı kart ile çekim şuan desteklenmiyor.',
            order_number: $request->order_number,
        );
    }

    public function deleteStoredCard(DeleteStoredCardRequest $request, MerchantAuth $auth): DeleteStoredCardResponse
    {
        return new DeleteStoredCardResponse(
            status: ResponseStatus::Error,
            message: 'Bu sanal pos için saklı kart silme şuan desteklenmiyor.',
        );
    }
}
