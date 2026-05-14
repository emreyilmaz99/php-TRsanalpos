<?php

namespace EvrenOnur\SanalPos\Gateways;

use EvrenOnur\SanalPos\Contracts\VirtualPOSServiceInterface;
use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\AdditionalInstallmentQueryRequest;
use EvrenOnur\SanalPos\DTOs\Requests\AllInstallmentQueryRequest;
use EvrenOnur\SanalPos\DTOs\Requests\BINInstallmentQueryRequest;
use EvrenOnur\SanalPos\DTOs\Requests\CancelRequest;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\Requests\RefundRequest;
use EvrenOnur\SanalPos\DTOs\Requests\SaleQueryRequest;
use EvrenOnur\SanalPos\DTOs\Responses\AdditionalInstallmentQueryResponse;
use EvrenOnur\SanalPos\DTOs\Responses\AllInstallmentQueryResponse;
use EvrenOnur\SanalPos\DTOs\Responses\BINInstallmentQueryResponse;
use EvrenOnur\SanalPos\DTOs\Responses\CancelResponse;
use EvrenOnur\SanalPos\DTOs\Responses\HostedPaymentResponse;
use EvrenOnur\SanalPos\DTOs\Responses\RefundResponse;
use EvrenOnur\SanalPos\DTOs\Responses\SaleQueryResponse;
use EvrenOnur\SanalPos\DTOs\Responses\SaleResponse;
use EvrenOnur\SanalPos\Enums\ResponseStatus;
use EvrenOnur\SanalPos\Enums\SaleQueryResponseStatus;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;
use EvrenOnur\SanalPos\Support\MakesHttpRequests;

/**
 * Tüm standalone gateway'ler için temel abstract sınıf.
 *
 * Desteklenmeyen işlemler için varsayılan stub yanıtlar sağlar.
 * Gateway'ler destekledikleri işlemleri override ederek gerçek implementasyon sağlar.
 */
abstract class AbstractGateway implements VirtualPOSServiceInterface
{
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
}
