<?php

namespace Emreyilmaz99\SanalPos\Testing;

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
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
use RuntimeException;

/**
 * Test sahnesindeki fake POS yöneticisi. SanalPos::fake() ile aktive edilir;
 * gerçek HTTP isteği yapılmadan önceden tanımlanmış response'lar döner ve tüm
 * çağrıları kayıt eder.
 *
 *   SanalPos::fake();
 *   SanalPos::fakeQueue('sale', new SaleResponse(status: SaleResponseStatus::Success, ...));
 *
 *   SanalPos::sale($req, $auth); // queued response döner
 *
 *   SanalPos::assertCalled('sale');
 *   SanalPos::assertCallCount('sale', 1);
 *
 *   SanalPos::fakeReset(); // teardown
 */
class FakePos
{
    private static ?self $instance = null;

    /** @var array<string, list<object>> method => queued responses */
    private array $responseQueue = [];

    /** @var array<string, list<array{request: object, auth: MerchantAuth}>> */
    private array $calls = [];

    private function __construct() {}

    public static function instance(): self
    {
        return self::$instance ??= new self;
    }

    public static function isActive(): bool
    {
        return self::$instance !== null;
    }

    public static function reset(): void
    {
        self::$instance = null;
    }

    /**
     * Bir metoda response queue'la. FIFO sırayla tüketilir. Queue boşalınca
     * sabit default response döner (her gateway metodu için uygun success/error).
     */
    public function queue(string $method, object $response): self
    {
        $this->responseQueue[$method][] = $response;

        return $this;
    }

    /**
     * Çağrıyı kaydet, queue'dan response al (yoksa default).
     */
    public function dispatch(string $method, object $request, MerchantAuth $auth): object
    {
        $this->calls[$method][] = ['request' => $request, 'auth' => $auth];

        if (! empty($this->responseQueue[$method])) {
            return array_shift($this->responseQueue[$method]);
        }

        return $this->defaultResponseFor($method, $request);
    }

    /**
     * @return list<array{request: object, auth: MerchantAuth}>
     */
    public function callsFor(string $method): array
    {
        return $this->calls[$method] ?? [];
    }

    public function callCount(string $method): int
    {
        return count($this->calls[$method] ?? []);
    }

    /**
     * Toplam tüm çağrı sayısı.
     */
    public function totalCalls(): int
    {
        $sum = 0;
        foreach ($this->calls as $list) {
            $sum += count($list);
        }

        return $sum;
    }

    public function assertCalled(string $method): void
    {
        if ($this->callCount($method) === 0) {
            throw new RuntimeException("Fake POS: '{$method}' çağrısı bekleniyordu, hiç yapılmadı.");
        }
    }

    public function assertNotCalled(string $method): void
    {
        $count = $this->callCount($method);
        if ($count > 0) {
            throw new RuntimeException("Fake POS: '{$method}' çağrılmamalıydı, {$count} kez yapıldı.");
        }
    }

    public function assertCallCount(string $method, int $expected): void
    {
        $actual = $this->callCount($method);
        if ($actual !== $expected) {
            throw new RuntimeException("Fake POS: '{$method}' için {$expected} çağrı beklendi, {$actual} gerçekleşti.");
        }
    }

    public function assertNothingSent(): void
    {
        $total = $this->totalCalls();
        if ($total > 0) {
            throw new RuntimeException("Fake POS: hiç çağrı beklenmiyordu, {$total} gerçekleşti.");
        }
    }

    /**
     * Bir çağrının request payload'ını closure ile doğrula.
     *
     * @param  callable(object $request, MerchantAuth $auth): bool  $predicate
     */
    public function assertSent(string $method, callable $predicate): void
    {
        foreach ($this->callsFor($method) as $call) {
            if ($predicate($call['request'], $call['auth'])) {
                return;
            }
        }
        throw new RuntimeException("Fake POS: '{$method}' için beklenen predicate'i sağlayan çağrı bulunamadı.");
    }

    private function defaultResponseFor(string $method, object $request): object
    {
        return match ($method) {
            'sale', 'sale3DResponse', 'chargeStoredCard', 'resolveHostedPayment' => new SaleResponse(
                status: SaleResponseStatus::Success,
                message: 'Fake success',
                order_number: $request->order_number ?? 'fake-order',
                transaction_id: 'fake-tx-' . bin2hex(random_bytes(4)),
            ),
            'cancel' => new CancelResponse(status: ResponseStatus::Success, message: 'Fake cancel'),
            'refund' => new RefundResponse(status: ResponseStatus::Success, message: 'Fake refund'),
            'saleQuery' => new SaleQueryResponse(status: SaleQueryResponseStatus::Found, message: 'Fake query'),
            'binInstallmentQuery' => new BINInstallmentQueryResponse(confirm: true),
            'allInstallmentQuery' => new AllInstallmentQueryResponse(confirm: true),
            'additionalInstallmentQuery' => new AdditionalInstallmentQueryResponse(confirm: true),
            'initializeHostedPayment' => new HostedPaymentResponse(
                status: ResponseStatus::Success,
                message: 'Fake hosted init',
                order_number: $request->order_number ?? 'fake-order',
            ),
            'storeCard' => new StoreCardResponse(
                status: ResponseStatus::Success,
                message: 'Fake store',
                card_token: 'fake-token-' . bin2hex(random_bytes(8)),
            ),
            'deleteStoredCard' => new DeleteStoredCardResponse(status: ResponseStatus::Success, message: 'Fake delete'),
            default => throw new RuntimeException("Fake POS: '{$method}' için default response tanımlı değil."),
        };
    }
}
