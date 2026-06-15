<?php

namespace Emreyilmaz99\SanalPos\Contracts;

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

interface VirtualPOSServiceInterface
{
    /**
     * Karttan çekim yapmak için kullanılır.
     * 3D çekim yapmak için payment_3d->confirm = true gönderilmelidir.
     */
    public function sale(SaleRequest $request, MerchantAuth $auth): SaleResponse;

    /**
     * 3D yapılan çekim işlemi sonucunu döner
     */
    public function sale3DResponse(Sale3DResponse $request, MerchantAuth $auth): SaleResponse;

    /**
     * Karta yapılabilecek taksit sayısını döner
     */
    public function binInstallmentQuery(BINInstallmentQueryRequest $request, MerchantAuth $auth): BINInstallmentQueryResponse;

    /**
     * Tutar ile taksit sayısını döner
     */
    public function allInstallmentQuery(AllInstallmentQueryRequest $request, MerchantAuth $auth): AllInstallmentQueryResponse;

    /**
     * Satış yapılabilecek ek taksit kampanyalarını döner
     */
    public function additionalInstallmentQuery(AdditionalInstallmentQueryRequest $request, MerchantAuth $auth): AdditionalInstallmentQueryResponse;

    /**
     * Ödeme iptal etme. Aynı gün yapılan ödemeler için kullanılabilir.
     */
    public function cancel(CancelRequest $request, MerchantAuth $auth): CancelResponse;

    /**
     * Ödeme iade etme. Belirtilen tutar kadar kısmi iade işlemi yapılır.
     */
    public function refund(RefundRequest $request, MerchantAuth $auth): RefundResponse;

    /**
     * Tekil işlem sorgulama
     */
    public function saleQuery(SaleQueryRequest $request, MerchantAuth $auth): SaleQueryResponse;

    /**
     * Hosted ödeme akışı başlatır. Kart bilgisi gerektirmez; bankanın
     * barındırdığı ödeme sayfasına yönlendirme/form üretir. Kart kullanıcı
     * tarafından banka sayfasında girilir.
     */
    public function initializeHostedPayment(HostedPaymentRequest $request, MerchantAuth $auth): HostedPaymentResponse;

    /**
     * Hosted ödeme akışında banka geri dönüşünü (success/fail callback) doğrular
     * ve nihai SaleResponse'a çevirir.
     */
    public function resolveHostedPayment(HostedPaymentCallback $callback, MerchantAuth $auth): SaleResponse;

    /**
     * Kart saklama — bankaya/gateway'e özgü token üretir. Token sonraki çekimlerde
     * card_number yerine kullanılır (PCI-DSS sahasını daraltır).
     */
    public function storeCard(StoreCardRequest $request, MerchantAuth $auth): StoreCardResponse;

    /**
     * Saklanmış kart token'ı ile çekim.
     */
    public function chargeStoredCard(ChargeStoredCardRequest $request, MerchantAuth $auth): SaleResponse;

    /**
     * Saklanmış kartı siler.
     */
    public function deleteStoredCard(DeleteStoredCardRequest $request, MerchantAuth $auth): DeleteStoredCardResponse;
}
