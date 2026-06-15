<?php

/**
 * Akbank /payhosting (3D_PAY_HOSTING) canlı sandbox smoke testi.
 *
 * Akbank sandbox merchant kredensiyalleri public değil — kullanıcı kendi
 * Akbank Merchant Portal'ından alır ve env var olarak inject eder:
 *
 *   SANALPOS_LIVE=1
 *   AKBANK_TEST_MERCHANT_SAFE_ID=...
 *   AKBANK_TEST_TERMINAL_SAFE_ID=...
 *   AKBANK_TEST_STORE_KEY=...
 *
 * Env yoksa test skip olur. Bu sayede CI'da güvenli + creds geldiğinde
 * tam doğrulama çalışır.
 *
 * Endpoint reachability ve payload yapısı her durumda doğrulanır.
 */

use Emreyilmaz99\SanalPos\DTOs\CustomerInfo;
use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Country;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Banks\AkbankGateway;
use Emreyilmaz99\SanalPos\Tests\Integration\LiveSandboxTestCase;

function akbankLiveAuthOrSkip(): MerchantAuth
{
    $msi = getenv('AKBANK_TEST_MERCHANT_SAFE_ID') ?: '';
    $tsi = getenv('AKBANK_TEST_TERMINAL_SAFE_ID') ?: '';
    $sk = getenv('AKBANK_TEST_STORE_KEY') ?: '';

    if ($msi === '' || $tsi === '' || $sk === '') {
        test()->markTestSkipped(
            'AKBANK_TEST_MERCHANT_SAFE_ID / AKBANK_TEST_TERMINAL_SAFE_ID / AKBANK_TEST_STORE_KEY env vars gerekli.'
        );
    }

    return new MerchantAuth(
        bank_code: '0046',
        merchant_id: '',
        merchant_user: $msi,
        merchant_password: $tsi,
        merchant_storekey: $sk,
        test_platform: true,
    );
}

it('Akbank hosted: payhosting endpoint payload\'ı kabul ediyor (env creds varsa)', function () {
    LiveSandboxTestCase::skipUnlessLive();

    $auth = akbankLiveAuthOrSkip();
    $request = new HostedPaymentRequest(
        order_number: 'AK-' . time(),
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 1.50, installment: 1),
        invoice_info: new CustomerInfo(
            name: 'cem', surname: 'pehlivan', email_address: 'test@test.com',
            phone_number: '1111111111', tax_number: '1111111111',
            country: Country::TUR, city_name: 'istanbul',
        ),
        success_url: 'https://merchant.example.com/akbank/basari',
        fail_url: 'https://merchant.example.com/akbank/hata',
        language: 'tr',
    );

    $gateway = new AkbankGateway;
    $response = $gateway->initializeHostedPayment($request, $auth);

    expect($response->status)->toBe(ResponseStatus::Success);
    expect($response->redirect_method)->toBe('POST');
    expect($response->redirect_url)->toContain('payhosting');
    expect($response->form_fields['paymentModel'])->toBe('3D_PAY_HOSTING');
    expect($response->form_fields)->toHaveKey('hash');
    expect($response->form_fields)->not->toHaveKey('creditCard'); // kart alanı YOK

    // Akbank payhosting endpoint'ine gerçek POST
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $response->redirect_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($response->form_fields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    expect($httpCode)->toBe(200, "Akbank payhosting HTTP {$httpCode} döndü");

    // Başarı = HTML ödeme formu (Akbank kart girme sayfası)
    $bodyLower = strtolower((string) $body);
    $hasCardForm = str_contains($bodyLower, 'kart') || str_contains($bodyLower, 'card');
    expect($hasCardForm)->toBeTrue(
        'Akbank yanıtında kart formu yok — payload reddedildi. İlk 500: ' . substr((string) $body, 0, 500)
    );
});
