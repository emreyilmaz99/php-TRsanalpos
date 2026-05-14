<?php

/**
 * NestPay ailesi (12 banka) hosted-payment testleri.
 *
 * AbstractNestpayGateway üzerinden 3D_PAY_HOSTING akışı sağlanır.
 * Test subject: IsBankasiGateway (mevcut VposDocumentationTest test ortamı).
 */

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\SaleInfo;
use EvrenOnur\SanalPos\Enums\Currency;
use EvrenOnur\SanalPos\Enums\ResponseStatus;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;
use EvrenOnur\SanalPos\Gateways\Banks\Nestpay\IsBankasiGateway;

function nestpayIsBankasiAuth(): MerchantAuth
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

function nestpayHostedRequest(): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: 'ORDER-NESTPAY-1',
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(
            currency: Currency::TRY,
            amount: 250.00,
            installment: 3,
        ),
        success_url: 'https://merchant.example/ok',
        fail_url: 'https://merchant.example/fail',
        language: 'tr',
    );
}

it('NestPay hosted initialize POST form ile gateway 3D URL\'sini döner', function () {
    $gw = new IsBankasiGateway;
    $res = $gw->initializeHostedPayment(nestpayHostedRequest(), nestpayIsBankasiAuth());

    expect($res->status)->toBe(ResponseStatus::Success);
    expect($res->redirect_method)->toBe('POST');
    expect($res->redirect_url)->toContain('est3Dgate'); // İş Bankası test 3D endpoint
    expect($res->form_fields)->toBeArray()->not->toBeEmpty();
});

it('NestPay hosted form storetype=3d_pay_hosting ve kart alanı içermez', function () {
    $gw = new IsBankasiGateway;
    $res = $gw->initializeHostedPayment(nestpayHostedRequest(), nestpayIsBankasiAuth());

    expect($res->form_fields)->toHaveKey('storetype');
    expect($res->form_fields['storetype'])->toBe('3d_pay_hosting');
    expect($res->form_fields)->not->toHaveKey('pan');
    expect($res->form_fields)->not->toHaveKey('cv2');
    expect($res->form_fields)->not->toHaveKey('Ecom_Payment_Card_ExpDate_Year');
});

it('NestPay hosted form ver3 SHA512 hash içerir ve recipe doğrulanabilir', function () {
    $gw = new IsBankasiGateway;
    $auth = nestpayIsBankasiAuth();
    $res = $gw->initializeHostedPayment(nestpayHostedRequest(), $auth);

    expect($res->form_fields)->toHaveKey('hash');
    $providedHash = $res->form_fields['hash'];

    // Aynı recipe ile lokal hesap → form içindeki hash ile eşleşmeli
    $params = $res->form_fields;
    unset($params['hash']);
    ksort($params);
    $vals = [];
    foreach ($params as $v) {
        $vals[] = str_replace('\\', '\\\\', str_replace('|', '\\|', (string) $v));
    }
    $expected = base64_encode(hash('sha512', implode('|', $vals) . '|' . $auth->merchant_storekey, true));

    expect($providedHash)->toBe($expected);
});

it('NestPay hosted resolveHostedPayment ProcReturnCode=00 + mdStatus=1 ile Success döner', function () {
    $gw = new IsBankasiGateway;
    $auth = nestpayIsBankasiAuth();

    $payload = [
        'oid' => 'ORDER-NESTPAY-1',
        'mdStatus' => '1',
        'ProcReturnCode' => '00',
        'TransId' => 'TX-555',
        'HASHPARAMS' => '',
        'HASH' => '',
    ];

    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'ORDER-NESTPAY-1', payload: $payload), $auth);

    expect($res->status)->toBe(SaleResponseStatus::Success);
    expect($res->transaction_id)->toBe('TX-555');
});

it('NestPay hosted resolveHostedPayment geçersiz hash callback\'i reddeder', function () {
    $gw = new IsBankasiGateway;
    $auth = nestpayIsBankasiAuth();

    $payload = [
        'oid' => 'ORDER-NESTPAY-1',
        'mdStatus' => '1',
        'ProcReturnCode' => '00',
        'HASHPARAMS' => 'oid:mdStatus:',
        'HASH' => 'KASTEN_YANLIS_HASH',
    ];

    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'ORDER-NESTPAY-1', payload: $payload), $auth);

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toContain('hash');
});
