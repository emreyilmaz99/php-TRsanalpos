<?php

/**
 * Iyzico CheckoutForm canlı sandbox smoke test.
 *
 * Iyzico kredensiyalleri sahibinin (merchant) kişisel API anahtarlarıdır;
 * sandbox dahi olsa GitHub'a hard-code edilmez. Environment variables üzerinden
 * okunur:
 *
 *   SANALPOS_LIVE=1
 *   IYZICO_TEST_API_KEY=sandbox-...
 *   IYZICO_TEST_SECRET_KEY=sandbox-...
 *
 * Çalıştır:
 *   SANALPOS_LIVE=1 IYZICO_TEST_API_KEY=... IYZICO_TEST_SECRET_KEY=... \
 *     vendor/bin/pest --testsuite=Integration --filter=IyzicoHosted
 *
 * Public docs: https://docs.iyzico.com
 */

use Emreyilmaz99\SanalPos\DTOs\CustomerInfo;
use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Country;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Providers\IyzicoGateway;
use Emreyilmaz99\SanalPos\Tests\Integration\LiveSandboxTestCase;

function iyzicoLiveAuthOrSkip(): MerchantAuth
{
    $apiKey = getenv('IYZICO_TEST_API_KEY') ?: '';
    $secretKey = getenv('IYZICO_TEST_SECRET_KEY') ?: '';

    if ($apiKey === '' || $secretKey === '') {
        test()->markTestSkipped(
            'IYZICO_TEST_API_KEY ve IYZICO_TEST_SECRET_KEY environment variables gerekli.'
        );
    }

    return new MerchantAuth(
        bank_code: '9999',
        merchant_id: '',
        merchant_user: $apiKey,
        merchant_password: $secretKey,
        merchant_storekey: '',
        test_platform: true,
    );
}

it('Iyzico CheckoutForm initialize sandbox tarafından kabul edilir, paymentPageUrl döner', function () {
    LiveSandboxTestCase::skipUnlessLive();

    $auth = iyzicoLiveAuthOrSkip();
    $gateway = new IyzicoGateway;

    $request = new HostedPaymentRequest(
        order_number: 'IYZ-SMOKE-' . time(),
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 1.50, installment: 1),
        invoice_info: new CustomerInfo(
            name: 'cem', surname: 'pehlivan', email_address: 'test@test.com',
            phone_number: '5551112233', tax_number: '11111111111',
            country: Country::TUR, city_name: 'istanbul',
            address_description: 'Test mahallesi test sokak no 1',
        ),
        success_url: 'https://merchant.example.com/iyzico/basari',
        fail_url: 'https://merchant.example.com/iyzico/hata',
    );

    $response = $gateway->initializeHostedPayment($request, $auth);

    expect($response->status)
        ->toBe(ResponseStatus::Success, 'Iyzico initialize başarısız: ' . $response->message);

    expect($response->redirect_method)->toBe('GET');
    expect($response->redirect_url)->toContain('iyzipay.com'); // sandbox-cpp.iyzipay.com veya benzeri
    expect($response->token)->not->toBeEmpty();
});

it('Iyzico geçersiz token ile retrieve\'de errorMessage döner', function () {
    LiveSandboxTestCase::skipUnlessLive();

    $auth = iyzicoLiveAuthOrSkip();
    $gateway = new IyzicoGateway;

    $callback = new HostedPaymentCallback(
        order_number: 'IYZ-INVALID',
        payload: [],
        token: 'invalid-token-' . bin2hex(random_bytes(8)),
    );

    $response = $gateway->resolveHostedPayment($callback, $auth);

    // Iyzico geçersiz token'a 'failure' status döner
    expect($response->status)->toBe(SaleResponseStatus::Error);
    expect($response->private_response)->toBeArray();
});
