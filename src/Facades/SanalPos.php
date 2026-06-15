<?php

namespace Emreyilmaz99\SanalPos\Facades;

use Emreyilmaz99\SanalPos\SanalPosClient;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\SaleResponse sale(\Emreyilmaz99\SanalPos\DTOs\Requests\SaleRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\SaleResponse sale3DResponse(\Emreyilmaz99\SanalPos\DTOs\Requests\Sale3DResponse $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\BINInstallmentQueryResponse binInstallmentQuery(\Emreyilmaz99\SanalPos\DTOs\Requests\BINInstallmentQueryRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\AllInstallmentQueryResponse allInstallmentQuery(\Emreyilmaz99\SanalPos\DTOs\Requests\AllInstallmentQueryRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\AdditionalInstallmentQueryResponse additionalInstallmentQuery(\Emreyilmaz99\SanalPos\DTOs\Requests\AdditionalInstallmentQueryRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\CancelResponse cancel(\Emreyilmaz99\SanalPos\DTOs\Requests\CancelRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\RefundResponse refund(\Emreyilmaz99\SanalPos\DTOs\Requests\RefundRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\SaleQueryResponse saleQuery(\Emreyilmaz99\SanalPos\DTOs\Requests\SaleQueryRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\HostedPaymentResponse initializeHostedPayment(\Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\SaleResponse resolveHostedPayment(\Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback $callback, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\StoreCardResponse storeCard(\Emreyilmaz99\SanalPos\DTOs\Requests\StoreCardRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\SaleResponse chargeStoredCard(\Emreyilmaz99\SanalPos\DTOs\Requests\ChargeStoredCardRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\DTOs\Responses\DeleteStoredCardResponse deleteStoredCard(\Emreyilmaz99\SanalPos\DTOs\Requests\DeleteStoredCardRequest $request, \Emreyilmaz99\SanalPos\DTOs\MerchantAuth $auth)
 * @method static \Emreyilmaz99\SanalPos\Testing\FakePos fake()
 * @method static void fakeQueue(string $method, object $response)
 * @method static void fakeReset()
 * @method static void assertCalled(string $method)
 * @method static void assertNotCalled(string $method)
 * @method static void assertCallCount(string $method, int $expected)
 * @method static void assertNothingSent()
 * @method static void assertSent(string $method, callable $predicate)
 * @method static array allBankList(?callable $filter = null)
 *
 * @see SanalPosClient
 */
class SanalPos extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'sanalpos';
    }
}
