<?php

/**
 * Ziraat Bankası NestPay 3D_PAY_HOSTING canlı sandbox smoke testi.
 *
 * Resmi Payten test docs (entegrasyon.asseco-see.com.tr):
 *   POST URL : https://entegrasyon.asseco-see.com.tr/fim/est3Dgate
 *   Client ID: 190300000
 *   StoreKey : 123456
 *   Model    : 3d_pay_hosting
 *
 * Mevcut sanalpos ZiraatBankasiGateway eski `torus-stage-ziraat.asseco-see.com.tr`
 * endpoint'ini kullanıyor — doc'taki yeni endpoint ile doğrulayalım. Eğer mevcut
 * endpoint'imiz halen çalışıyorsa öyle kalır; çalışmıyorsa endpoint'i update ederiz.
 *
 * Çalıştır: SANALPOS_LIVE=1 vendor/bin/pest --testsuite=Integration --filter=Ziraat
 */

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\SaleInfo;
use EvrenOnur\SanalPos\Enums\Currency;
use EvrenOnur\SanalPos\Enums\ResponseStatus;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\ZiraatBankasiGateway;
use EvrenOnur\SanalPos\Tests\Integration\LiveSandboxTestCase;

function ziraatLiveAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '0010',
        merchant_id: '190300000',     // clientid (3d_pay_hosting test mağazası)
        merchant_user: '',             // hosted-form için kullanılmaz
        merchant_password: '',
        merchant_storekey: '123456',
        test_platform: true,
    );
}

it('Ziraat 3D_PAY_HOSTING: mevcut gateway endpoint sandbox tarafından kabul ediliyor', function () {
    LiveSandboxTestCase::skipUnlessLive();

    $request = new HostedPaymentRequest(
        order_number: 'ZRT-' . time(),
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 1.50, installment: 1),
        success_url: 'https://merchant.example.com/ziraat/basari',
        fail_url: 'https://merchant.example.com/ziraat/hata',
        language: 'tr',
    );

    $gateway = new ZiraatBankasiGateway;
    $response = $gateway->initializeHostedPayment($request, ziraatLiveAuth());

    expect($response->status)->toBe(ResponseStatus::Success);
    expect($response->redirect_method)->toBe('POST');
    expect($response->form_fields['storetype'])->toBe('3d_pay_hosting');
    expect($response->form_fields['clientid'])->toBe('190300000');

    // Gerçek HTTP POST — form payload kabul ediliyor mu?
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

    expect($httpCode)->toBe(200, "Ziraat 3D gateway HTTP {$httpCode} döndü. URL: {$response->redirect_url}");

    $bodyLower = strtolower((string) $body);
    $hasFormElement = str_contains($bodyLower, '<form') || str_contains($bodyLower, '<input');
    expect($hasFormElement)->toBeTrue(
        'Yanıtta HTML form yok — payload reddedildi. İlk 500: ' . substr((string) $body, 0, 500)
    );
});
