<?php

/**
 * PayNKolay (N Kolay / Aktif Bank) hosted ödeme testleri.
 * Hash recipe sale3D ile aynı, kart alanları absent, /Vpos/Default.aspx form-POST.
 */

use EvrenOnur\SanalPos\Contracts\Capabilities\SupportsHostedPayment;
use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\SaleInfo;
use EvrenOnur\SanalPos\Enums\Currency;
use EvrenOnur\SanalPos\Enums\ResponseStatus;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;
use EvrenOnur\SanalPos\Gateways\Providers\PayNKolayGateway;

function payNKolayAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '9975',
        merchant_id: 'TESTMERCHANT',
        merchant_user: 'USER',
        merchant_password: 'PASS',
        merchant_storekey: 'CUSTOMERKEY',
        test_platform: true,
    );
}

function payNKolayHostedRequest(): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: 'ORDER-NKOLAY-1',
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 75.50, installment: 1),
        success_url: 'https://merchant.example/ok',
        fail_url: 'https://merchant.example/fail',
    );
}

it('PayNKolay hosted SupportsHostedPayment marker taşır', function () {
    expect(new PayNKolayGateway)->toBeInstanceOf(SupportsHostedPayment::class);
});

it('PayNKolay hosted /Vpos/Default.aspx\'a POST eder ve kart alanı içermez', function () {
    $res = (new PayNKolayGateway)->initializeHostedPayment(payNKolayHostedRequest(), payNKolayAuth());

    expect($res->status)->toBe(ResponseStatus::Success);
    expect($res->redirect_method)->toBe('POST');
    expect($res->redirect_url)->toContain('Vpos/Default.aspx');
    expect($res->form_fields)->not->toHaveKey('cardNo');
    expect($res->form_fields)->not->toHaveKey('cvc');
    expect($res->form_fields)->not->toHaveKey('cardHolderName');
});

it('PayNKolay hosted hash recipe doğrulanabilir', function () {
    $auth = payNKolayAuth();
    $req = payNKolayHostedRequest();
    $res = (new PayNKolayGateway)->initializeHostedPayment($req, $auth);
    $f = $res->form_fields;

    $expected = base64_encode(hash('sha512', implode('|', [
        $auth->merchant_id,
        $req->order_number,
        $f['amount'],
        $req->success_url,
        $req->fail_url,
        $f['rnd'],
        $auth->merchant_storekey,
        $auth->merchant_password,
    ]), true));

    expect($f['hash'])->toBe($expected);
});

it('PayNKolay hosted resolveHostedPayment RESPONSE_CODE=2 + AUTH_CODE → Success', function () {
    $payload = [
        'CLIENT_REFERENCE_CODE' => 'ORDER-NKOLAY-1',
        'RESPONSE_CODE' => '2',
        'AUTH_CODE' => '123456',
        'REFERENCE_CODE' => 'REF-789',
    ];
    $res = (new PayNKolayGateway)->resolveHostedPayment(
        new HostedPaymentCallback(order_number: 'ORDER-NKOLAY-1', payload: $payload),
        payNKolayAuth()
    );

    expect($res->status)->toBe(SaleResponseStatus::Success);
    expect($res->transaction_id)->toBe('REF-789');
});

it('PayNKolay hosted AUTH_CODE=0 → Error', function () {
    $payload = ['CLIENT_REFERENCE_CODE' => 'X', 'RESPONSE_CODE' => '2', 'AUTH_CODE' => '0', 'RESPONSE_MSG' => 'declined'];
    $res = (new PayNKolayGateway)->resolveHostedPayment(
        new HostedPaymentCallback(order_number: 'X', payload: $payload),
        payNKolayAuth()
    );

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toBe('declined');
});
