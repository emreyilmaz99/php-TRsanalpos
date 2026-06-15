<?php

/**
 * Garanti BBVA "Güvenli Ortak Ödeme Sayfası" (3D_OOS_PAY) canlı sandbox smoke test.
 *
 * Garanti'nin test sandbox'ına gerçek HTTP POST atar; sayfa açılırsa hash recipe
 * ve payload yapısının kabul edildiğini doğrular. vpos.com.tr public test
 * kredensiyalleri kullanır.
 *
 * Çalıştır: SANALPOS_LIVE=1 vendor/bin/pest --testsuite=Integration --filter=GarantiHosted
 */

use Emreyilmaz99\SanalPos\DTOs\CustomerInfo;
use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Country;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Banks\GarantiBBVAGateway;
use Emreyilmaz99\SanalPos\Tests\Integration\LiveSandboxTestCase;

it('Garanti hosted: form payload sandbox tarafından kabul edilir, ödeme sayfası açılır', function () {
    LiveSandboxTestCase::skipUnlessLive();

    $auth = new MerchantAuth(
        bank_code: '0062',
        merchant_id: '7000679',
        merchant_user: '30691297',
        merchant_password: '123qweASD/',
        merchant_storekey: '12345678',
        test_platform: true,
    );

    $request = new HostedPaymentRequest(
        order_number: 'SMOKE-' . time(),
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 1.50, installment: 1),
        invoice_info: new CustomerInfo(
            name: 'cem', surname: 'pehlivan', email_address: 'test@test.com',
            phone_number: '1111111111', tax_number: '1111111111',
            country: Country::TUR, city_name: 'istanbul',
        ),
        success_url: 'https://merchant.example.com/odeme/basari',
        fail_url: 'https://merchant.example.com/odeme/hata',
    );

    $response = (new GarantiBBVAGateway)->initializeHostedPayment($request, $auth);
    expect($response->status)->toBe(ResponseStatus::Success);

    $result = LiveSandboxTestCase::postFormToGateway($response->redirect_url, $response->form_fields);

    expect($result['error'])->toBeNull();
    expect($result['status'])->toBe(200);
    expect(LiveSandboxTestCase::bodyContainsAll($result['body'], [
        'Güvenli Ortak Ödeme Sayfası',
        'Kart Numarası',
    ]))->toBeTrue('Garanti sandbox ödeme sayfası dönmedi — payload reddedildi olabilir');
});
