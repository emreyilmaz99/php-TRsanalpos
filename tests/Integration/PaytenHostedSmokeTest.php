<?php

/**
 * Payten (MSU) hosted SESSIONTOKEN canlı sandbox smoke testi.
 *
 * Payten sandbox kredensiyalleri public değil — Payten Entegrasyon Merkezi'nden
 * alınır. Env var olarak inject edilir:
 *
 *   SANALPOS_LIVE=1
 *   PAYTEN_TEST_MERCHANT=...
 *   PAYTEN_TEST_USER=...
 *   PAYTEN_TEST_PASSWORD=...
 *
 * Aynı recipe AbstractPaytenGateway alt sınıflarında (Paratika, VakıfPaySG,
 * ZiraatPay) de geçerli — endpoint URL'i değiştirerek hepsi test edilebilir.
 */

use Emreyilmaz99\SanalPos\DTOs\CustomerInfo;
use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Country;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Providers\Payten\PaytenGateway;
use Emreyilmaz99\SanalPos\Tests\Integration\LiveSandboxTestCase;

function paytenLiveAuthOrSkip(): MerchantAuth
{
    $merchant = getenv('PAYTEN_TEST_MERCHANT') ?: '';
    $user = getenv('PAYTEN_TEST_USER') ?: '';
    $password = getenv('PAYTEN_TEST_PASSWORD') ?: '';

    if ($merchant === '' || $user === '' || $password === '') {
        test()->markTestSkipped(
            'PAYTEN_TEST_MERCHANT / PAYTEN_TEST_USER / PAYTEN_TEST_PASSWORD env vars gerekli.'
        );
    }

    return new MerchantAuth(
        bank_code: '9979',
        merchant_id: $merchant,
        merchant_user: $user,
        merchant_password: $password,
        merchant_storekey: '',
        test_platform: true,
    );
}

it('Payten hosted: SESSIONTOKEN action API\'ı kabul ediyor (env creds varsa)', function () {
    LiveSandboxTestCase::skipUnlessLive();

    $auth = paytenLiveAuthOrSkip();
    $request = new HostedPaymentRequest(
        order_number: 'PT-' . time(),
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 1.50, installment: 1),
        invoice_info: new CustomerInfo(
            name: 'cem', surname: 'pehlivan', email_address: 'test@test.com',
            phone_number: '1111111111', tax_number: '1111111111',
            country: Country::TUR, city_name: 'istanbul',
        ),
        success_url: 'https://merchant.example.com/payten/basari',
        fail_url: 'https://merchant.example.com/payten/hata',
        language: 'tr',
    );

    $gateway = new PaytenGateway;
    $response = $gateway->initializeHostedPayment($request, $auth);

    expect($response->status)->toBe(ResponseStatus::Success);
    expect($response->redirect_method)->toBe('GET');
    expect($response->redirect_url)->toContain('sale3d');
    expect($response->token)->not->toBeEmpty(); // SESSIONTOKEN alındı
});
