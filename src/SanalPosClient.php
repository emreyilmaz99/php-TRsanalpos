<?php

namespace Emreyilmaz99\SanalPos;

use Emreyilmaz99\SanalPos\Contracts\VirtualPOSServiceInterface;
use Emreyilmaz99\SanalPos\DTOs\Bank;
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
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Events\EventDispatcher;
use Emreyilmaz99\SanalPos\Events\PaymentFailed;
use Emreyilmaz99\SanalPos\Events\PaymentInitiated;
use Emreyilmaz99\SanalPos\Events\PaymentSucceeded;
use Emreyilmaz99\SanalPos\Exceptions\DuplicateRequestException;
use Emreyilmaz99\SanalPos\Services\BankService;
use Emreyilmaz99\SanalPos\Support\IdempotencyStore;
use Emreyilmaz99\SanalPos\Support\InMemoryIdempotencyStore;
use Emreyilmaz99\SanalPos\Support\LaravelCacheIdempotencyStore;
use Emreyilmaz99\SanalPos\Support\StringHelper;
use Emreyilmaz99\SanalPos\Support\ValidationHelper;
use Emreyilmaz99\SanalPos\Testing\FakePos;

class SanalPosClient
{
    private static ?IdempotencyStore $idempotencyStore = null;

    /**
     * Idempotency store inject etmek için. Verilmezse Laravel cache (varsa) veya
     * InMemory store otomatik kullanılır.
     */
    public static function setIdempotencyStore(?IdempotencyStore $store): void
    {
        self::$idempotencyStore = $store;
    }

    public static function idempotencyStore(): IdempotencyStore
    {
        if (self::$idempotencyStore !== null) {
            return self::$idempotencyStore;
        }

        if (function_exists('cache')) {
            return self::$idempotencyStore = new LaravelCacheIdempotencyStore;
        }

        return self::$idempotencyStore = new InMemoryIdempotencyStore;
    }

    /**
     * Karttan çekim yapmak için kullanılır.
     * 3D çekim yapmak için payment_3d->confirm = true gönderilmelidir.
     */
    public static function sale(SaleRequest $request, MerchantAuth $auth): SaleResponse
    {
        ValidationHelper::validateSaleRequest($request);
        ValidationHelper::validateAuth($auth);

        // Idempotency guard — anahtar verilmişse aynı anahtarla tekrar atılırsa fırlat
        if ($request->idempotency_key !== null && self::idempotencyStore()->seen('sale:' . $request->idempotency_key)) {
            throw new DuplicateRequestException($request->idempotency_key);
        }

        // Adres sanitizasyonu
        $request->invoice_info = ValidationHelper::sanitizeCustomerInfo($request->invoice_info);
        $request->shipping_info = ValidationHelper::sanitizeCustomerInfo($request->shipping_info);
        $request->sale_info->card_name_surname = StringHelper::clearString($request->sale_info->card_name_surname);

        EventDispatcher::dispatch(new PaymentInitiated($auth->bank_code, $request->order_number, $auth, ['flow' => 'sale']));

        $gateway = self::getGateway($auth->bank_code);
        $response = $gateway->sale($request, $auth);

        self::dispatchPaymentOutcome($auth, $request->order_number, $response, 'sale');

        return $response;
    }

    /**
     * 3D yapılan çekim işlemi sonucunu döner
     */
    public static function sale3DResponse(Sale3DResponse $request, MerchantAuth $auth): SaleResponse
    {
        ValidationHelper::validateAuth($auth);

        // JArray normalizasyonu (array içinde array varsa ilk elemanı al)
        if (is_array($request->responseArray)) {
            foreach ($request->responseArray as $key => $value) {
                if (is_array($value) && isset($value[0]) && array_is_list($value)) {
                    $request->responseArray[$key] = $value[0];
                }
            }
        }

        $gateway = self::getGateway($auth->bank_code);
        $response = $gateway->sale3DResponse($request, $auth);

        self::dispatchPaymentOutcome($auth, $response->order_number, $response, 'sale3DResponse');

        return $response;
    }

    /**
     * Karta yapılabilecek taksit sayısını döner
     */
    public static function binInstallmentQuery(BINInstallmentQueryRequest $request, MerchantAuth $auth): BINInstallmentQueryResponse
    {
        ValidationHelper::validateBINInstallmentQuery($request);
        ValidationHelper::validateAuth($auth);

        $gateway = self::getGateway($auth->bank_code);

        return $gateway->binInstallmentQuery($request, $auth);
    }

    /**
     * Tutar ile taksit sayısını döner
     */
    public static function allInstallmentQuery(AllInstallmentQueryRequest $request, MerchantAuth $auth): AllInstallmentQueryResponse
    {
        ValidationHelper::validateAuth($auth);

        $gateway = self::getGateway($auth->bank_code);

        return $gateway->allInstallmentQuery($request, $auth);
    }

    /**
     * Satış yapılabilecek ek taksit kampanyalarını döner
     */
    public static function additionalInstallmentQuery(AdditionalInstallmentQueryRequest $request, MerchantAuth $auth): AdditionalInstallmentQueryResponse
    {
        ValidationHelper::validateAuth($auth);

        $gateway = self::getGateway($auth->bank_code);

        return $gateway->additionalInstallmentQuery($request, $auth);
    }

    /**
     * Ödeme iptal eder. Aynı gün yapılan ödemeler için kullanılabilir.
     */
    public static function cancel(CancelRequest $request, MerchantAuth $auth): CancelResponse
    {
        ValidationHelper::validateCancelRequest($request);
        ValidationHelper::validateAuth($auth);

        $gateway = self::getGateway($auth->bank_code);

        return $gateway->cancel($request, $auth);
    }

    /**
     * Ödeme iade eder. Belirtilen tutar kadar kısmi iade işlemi yapılır.
     */
    public static function refund(RefundRequest $request, MerchantAuth $auth): RefundResponse
    {
        ValidationHelper::validateRefundRequest($request);
        ValidationHelper::validateAuth($auth);

        $gateway = self::getGateway($auth->bank_code);

        return $gateway->refund($request, $auth);
    }

    /**
     * Tekil işlem sorgulama
     */
    public static function saleQuery(SaleQueryRequest $request, MerchantAuth $auth): SaleQueryResponse
    {
        $request->validate();
        ValidationHelper::validateAuth($auth);

        $gateway = self::getGateway($auth->bank_code);

        return $gateway->saleQuery($request, $auth);
    }

    /**
     * Hosted (banka barındırmalı) ödeme akışı başlatır. Kart bilgisi gerektirmez;
     * bankanın ödeme sayfasına yönlendirme/form üretir.
     */
    public static function initializeHostedPayment(HostedPaymentRequest $request, MerchantAuth $auth): HostedPaymentResponse
    {
        ValidationHelper::validateAuth($auth);

        $errors = $request->validate();
        if (! empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        if ($request->idempotency_key !== null && self::idempotencyStore()->seen('hosted:' . $request->idempotency_key)) {
            throw new DuplicateRequestException($request->idempotency_key);
        }

        $request->invoice_info = ValidationHelper::sanitizeCustomerInfo($request->invoice_info);
        $request->shipping_info = ValidationHelper::sanitizeCustomerInfo($request->shipping_info);

        EventDispatcher::dispatch(new PaymentInitiated($auth->bank_code, $request->order_number, $auth, ['flow' => 'hosted']));

        $gateway = self::getGateway($auth->bank_code);

        return $gateway->initializeHostedPayment($request, $auth);
    }

    /**
     * Hosted ödeme sonrası bankadan dönen callback'i çözer, SaleResponse'a çevirir.
     */
    public static function resolveHostedPayment(HostedPaymentCallback $callback, MerchantAuth $auth): SaleResponse
    {
        ValidationHelper::validateAuth($auth);

        $gateway = self::getGateway($auth->bank_code);
        $response = $gateway->resolveHostedPayment($callback, $auth);

        self::dispatchPaymentOutcome($auth, $response->order_number, $response, 'hosted_callback');

        return $response;
    }

    /**
     * Sale akışı sonrası uygun event'i dispatch eder (success / failure).
     */
    private static function dispatchPaymentOutcome(MerchantAuth $auth, string $orderNumber, SaleResponse $response, string $flow): void
    {
        if ($response->status === SaleResponseStatus::Success) {
            EventDispatcher::dispatch(new PaymentSucceeded(
                $auth->bank_code,
                $orderNumber,
                $response->transaction_id,
                $auth,
                ['flow' => $flow],
            ));
        } elseif ($response->status === SaleResponseStatus::Error) {
            EventDispatcher::dispatch(new PaymentFailed(
                $auth->bank_code,
                $orderNumber,
                $response->message,
                null,
                $auth,
                ['flow' => $flow],
            ));
        }
        // RedirectHTML / RedirectURL durumları henüz outcome değil — async tamamlanır.
    }

    /**
     * Kart saklama (tokenization). Bankaya/gateway'e özgü token üretir.
     */
    public static function storeCard(StoreCardRequest $request, MerchantAuth $auth): StoreCardResponse
    {
        ValidationHelper::validateAuth($auth);

        $errors = $request->validate();
        if (! empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        if ($request->idempotency_key !== null && self::idempotencyStore()->seen('store:' . $request->idempotency_key)) {
            throw new DuplicateRequestException($request->idempotency_key);
        }

        if ($request->card_holder_name !== '') {
            $request->card_holder_name = StringHelper::clearString($request->card_holder_name);
        }

        return self::getGateway($auth->bank_code)->storeCard($request, $auth);
    }

    /**
     * Saklı kart ile çekim.
     */
    public static function chargeStoredCard(ChargeStoredCardRequest $request, MerchantAuth $auth): SaleResponse
    {
        ValidationHelper::validateAuth($auth);

        $errors = $request->validate();
        if (! empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        if ($request->idempotency_key !== null && self::idempotencyStore()->seen('charge:' . $request->idempotency_key)) {
            throw new DuplicateRequestException($request->idempotency_key);
        }

        if ($request->invoice_info !== null) {
            $request->invoice_info = ValidationHelper::sanitizeCustomerInfo($request->invoice_info);
        }

        EventDispatcher::dispatch(new PaymentInitiated($auth->bank_code, $request->order_number, $auth, ['flow' => 'charge_stored_card']));

        $gateway = self::getGateway($auth->bank_code);
        $response = $gateway->chargeStoredCard($request, $auth);

        self::dispatchPaymentOutcome($auth, $request->order_number, $response, 'charge_stored_card');

        return $response;
    }

    /**
     * Saklı kartı sil.
     */
    public static function deleteStoredCard(DeleteStoredCardRequest $request, MerchantAuth $auth): DeleteStoredCardResponse
    {
        ValidationHelper::validateAuth($auth);

        $errors = $request->validate();
        if (! empty($errors)) {
            throw new \InvalidArgumentException(implode(' ', $errors));
        }

        return self::getGateway($auth->bank_code)->deleteStoredCard($request, $auth);
    }

    /**
     * Test sahnesi başlatır — bundan sonra tüm çağrılar FakeGateway'e gider,
     * gerçek HTTP isteği yapılmaz. Test teardown'da `fakeReset()` çağrılmalı.
     */
    public static function fake(): FakePos
    {
        return FakePos::instance();
    }

    /**
     * Belirli bir metoda response queue'la. FIFO sırayla tüketilir.
     */
    public static function fakeQueue(string $method, object $response): void
    {
        FakePos::instance()->queue($method, $response);
    }

    /**
     * Fake durumunu sıfırlar — test teardown.
     */
    public static function fakeReset(): void
    {
        FakePos::reset();
    }

    public static function assertCalled(string $method): void
    {
        FakePos::instance()->assertCalled($method);
    }

    public static function assertNotCalled(string $method): void
    {
        FakePos::instance()->assertNotCalled($method);
    }

    public static function assertCallCount(string $method, int $expected): void
    {
        FakePos::instance()->assertCallCount($method, $expected);
    }

    public static function assertNothingSent(): void
    {
        FakePos::instance()->assertNothingSent();
    }

    /**
     * @param  callable(object $request, MerchantAuth $auth): bool  $predicate
     */
    public static function assertSent(string $method, callable $predicate): void
    {
        FakePos::instance()->assertSent($method, $predicate);
    }

    /**
     * Tüm banka listesi. Opsiyonel filtre callback'i kullanılabilir.
     */
    public static function allBankList(?callable $filter = null): array
    {
        $banks = BankService::allBanks();

        if ($filter !== null) {
            $banks = array_filter($banks, $filter);
        }

        return array_values(array_map(function (Bank $bank) {
            return new Bank(
                bank_code: $bank->bank_code,
                bank_name: $bank->bank_name,
                collective_vpos: $bank->collective_vpos,
                commissionAutoAdd: $bank->commissionAutoAdd,
                installment_api: $bank->installment_api,
            );
        }, $banks));
    }

    /**
     * Gateway instance döner
     */
    private static function getGateway(string $bank_code): VirtualPOSServiceInterface
    {
        return BankService::createGateway($bank_code);
    }
}
