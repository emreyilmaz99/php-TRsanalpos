<?php

/**
 * NestPay İş Bankası 3D_PAY_HOSTING canlı sandbox smoke testi.
 *
 * Bankanın 3D gateway URL'sine form POST atılır; başarılı yanıt = HTML
 * ödeme formu döner (Iyzico CheckoutForm gibi GET-redirect değil, banka
 * sayfasını HTML olarak inline render eder).
 *
 * Public test creds: vpos.com.tr/docs.
 *   merchant_id=700655000200, merchant_user=ISBANKAPI, password=ISBANK07, storekey=TRPS0200
 *
 * Çalıştır: SANALPOS_LIVE=1 vendor/bin/pest --testsuite=Integration --filter=NestpayIsBankasi
 *
 * Aynı recipe (AbstractNestpayGateway) tüm NestPay ailesinde geçerli — Halkbank,
 * Ziraat, Akbank-NestPay, vs. için de endpoint URL'i değiştirip aynı şekilde
 * doğrulanabilir.
 */

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Banks\Nestpay\IsBankasiGateway;
use Emreyilmaz99\SanalPos\Tests\Integration\LiveSandboxTestCase;

function isBankasiLiveAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '0064',
        merchant_id: '700655000200',
        merchant_user: 'ISBANKAPI',
        merchant_password: 'ISBANK07',
        merchant_storekey: 'TRPS0200',
        test_platform: true,
    );
}

it('NestPay İş Bankası hosted: 3D gateway form payload\'ı kabul ediyor', function () {
    LiveSandboxTestCase::skipUnlessLive();

    $request = new HostedPaymentRequest(
        order_number: 'NP-IS-' . time(),
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 1.50, installment: 1),
        success_url: 'https://merchant.example.com/isbank/basari',
        fail_url: 'https://merchant.example.com/isbank/hata',
        language: 'tr',
    );

    $gateway = new IsBankasiGateway;
    $response = $gateway->initializeHostedPayment($request, isBankasiLiveAuth());

    expect($response->status)->toBe(ResponseStatus::Success);
    expect($response->redirect_method)->toBe('POST');
    expect($response->redirect_url)->toContain('istest.asseco-see.com.tr');
    expect($response->form_fields)->toHaveKey('hash');
    expect($response->form_fields['storetype'])->toBe('3d_pay_hosting');

    // Şimdi gerçek HTTP POST: bankanın 3D gateway endpoint'ine form gönder
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

    expect($httpCode)->toBe(200, "NestPay 3D gateway HTTP {$httpCode} döndü");

    // Başarı göstergeleri: HTML form, NestPay 3D Secure sayfası, kart formu içeriği
    $bodyLower = strtolower((string) $body);
    $hasFormElement = str_contains($bodyLower, '<form') || str_contains($bodyLower, '<input');
    expect($hasFormElement)->toBeTrue(
        'Yanıtta HTML form yok — payload reddedildi olabilir. İlk 500: ' . substr((string) $body, 0, 500)
    );

    // NestPay hata sayfası genellikle "Error" veya "ErrMsg" içerir — bu olmamalı
    expect(stripos($bodyLower, 'errmsg=') !== false && stripos($bodyLower, 'errmsg=&') === false)
        ->toBeFalse('NestPay ErrMsg dolu döndü: ' . substr((string) $body, 0, 500));
});
