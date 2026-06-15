<?php

/**
 * Akbank 3D_PAY_HOSTING (payhosting) testleri.
 *
 * Hash recipe sale3D ile aynı; tek fark kart alanlarının (creditCard/expiredDate/cvv) yokluğu.
 * Endpoint payhosting (securepay'den farklı).
 */

use Emreyilmaz99\SanalPos\DTOs\MerchantAuth;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentCallback;
use Emreyilmaz99\SanalPos\DTOs\Requests\HostedPaymentRequest;
use Emreyilmaz99\SanalPos\DTOs\SaleInfo;
use Emreyilmaz99\SanalPos\Enums\Currency;
use Emreyilmaz99\SanalPos\Enums\ResponseStatus;
use Emreyilmaz99\SanalPos\Enums\SaleResponseStatus;
use Emreyilmaz99\SanalPos\Gateways\Banks\AkbankGateway;

function akbankHostedAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '0046',
        merchant_id: '',
        merchant_user: 'AKBANKMRCH',
        merchant_password: 'AKBANKTERM',
        merchant_storekey: 'TESTSTOREKEY',
        test_platform: true,
    );
}

function akbankHostedRequest(): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: 'ORDER-AKBANK-1',
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(
            currency: Currency::TRY,
            amount: 175.50,
            installment: 1,
        ),
        success_url: 'https://merchant.example/ok',
        fail_url: 'https://merchant.example/fail',
        language: 'tr',
    );
}

it('Akbank hosted payhosting endpoint\'ini ve paymentModel=3D_PAY_HOSTING döner', function () {
    $gw = new AkbankGateway;
    $res = $gw->initializeHostedPayment(akbankHostedRequest(), akbankHostedAuth());

    expect($res->status)->toBe(ResponseStatus::Success);
    expect($res->redirect_method)->toBe('POST');
    expect($res->redirect_url)->toContain('payhosting');
    expect($res->redirect_url)->not->toContain('securepay');
    expect($res->form_fields['paymentModel'])->toBe('3D_PAY_HOSTING');
    expect($res->form_fields['txnCode'])->toBe('3000');
});

it('Akbank hosted form kart alanı içermez', function () {
    $gw = new AkbankGateway;
    $res = $gw->initializeHostedPayment(akbankHostedRequest(), akbankHostedAuth());

    expect($res->form_fields)->not->toHaveKey('creditCard');
    expect($res->form_fields)->not->toHaveKey('expiredDate');
    expect($res->form_fields)->not->toHaveKey('cvv');
});

it('Akbank hosted form HMAC-SHA512 hash içerir ve recipe doğrulanabilir', function () {
    $gw = new AkbankGateway;
    $auth = akbankHostedAuth();
    $res = $gw->initializeHostedPayment(akbankHostedRequest(), $auth);

    $f = $res->form_fields;
    $hashItems = $f['paymentModel'] . $f['txnCode'] . $f['merchantSafeId'] .
        $f['terminalSafeId'] . $f['orderId'] . $f['lang'] .
        $f['amount'] . $f['currencyCode'] . $f['installCount'] .
        $f['okUrl'] . $f['failUrl'] . $f['emailAddress'] .
        $f['randomNumber'] . $f['requestDateTime'];

    $expected = base64_encode(hash_hmac('sha512', $hashItems, $auth->merchant_storekey, true));

    expect($f['hash'])->toBe($expected);
});

it('Akbank hosted resolveHostedPayment VPS-0000 + mdStatus=1 → Success', function () {
    $gw = new AkbankGateway;
    $payload = [
        'orderId' => 'ORDER-AKBANK-1',
        'responseCode' => 'VPS-0000',
        'mdStatus' => '1',
        'authCode' => 'AUTH123',
    ];
    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'ORDER-AKBANK-1', payload: $payload), akbankHostedAuth());

    expect($res->status)->toBe(SaleResponseStatus::Success);
    expect($res->transaction_id)->toBe('AUTH123');
});

it('Akbank hosted resolveHostedPayment hata kodunu mesaja taşır', function () {
    $gw = new AkbankGateway;
    $payload = [
        'orderId' => 'X',
        'responseCode' => 'VPS-9999',
        'mdStatus' => '0',
        'responseMessage' => '3D doğrulaması yapılamadı',
    ];
    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'X', payload: $payload), akbankHostedAuth());

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toBe('3D doğrulaması yapılamadı');
});
