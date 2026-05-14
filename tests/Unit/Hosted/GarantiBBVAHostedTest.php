<?php

/**
 * Garanti BBVA 3D_PAY (hosted) testleri.
 *
 * Hash recipe sale3D ile aynı (uppercase SHA1); fark: secure3dsecuritylevel='3D_PAY'
 * ve kart alanları yok. Bankanın Common Payment Page'i kullanılır.
 */

use EvrenOnur\SanalPos\DTOs\MerchantAuth;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentCallback;
use EvrenOnur\SanalPos\DTOs\Requests\HostedPaymentRequest;
use EvrenOnur\SanalPos\DTOs\SaleInfo;
use EvrenOnur\SanalPos\Enums\Currency;
use EvrenOnur\SanalPos\Enums\ResponseStatus;
use EvrenOnur\SanalPos\Enums\SaleResponseStatus;
use EvrenOnur\SanalPos\Gateways\Banks\GarantiBBVAGateway;
use EvrenOnur\SanalPos\Support\StringHelper;

function garantiHostedAuth(): MerchantAuth
{
    return new MerchantAuth(
        bank_code: '0062',
        merchant_id: '7000679',
        merchant_user: '30691297',
        merchant_password: '123qweASD/',
        merchant_storekey: '12345678',
        test_platform: true,
    );
}

function garantiHostedRequest(): HostedPaymentRequest
{
    return new HostedPaymentRequest(
        order_number: 'ORDER-GAR-1',
        customer_ip_address: '1.2.3.4',
        sale_info: new SaleInfo(currency: Currency::TRY, amount: 200.00, installment: 1),
        success_url: 'https://merchant.example/ok',
        fail_url: 'https://merchant.example/fail',
    );
}

it('Garanti hosted gt3dengine\'a POST eder ve 3D_PAY seviyesi kullanır', function () {
    $gw = new GarantiBBVAGateway;
    $res = $gw->initializeHostedPayment(garantiHostedRequest(), garantiHostedAuth());

    expect($res->status)->toBe(ResponseStatus::Success);
    expect($res->redirect_method)->toBe('POST');
    expect($res->redirect_url)->toContain('gt3dengine');
    expect($res->form_fields['secure3dsecuritylevel'])->toBe('3D_PAY');
    expect($res->form_fields['txntype'])->toBe('sales');
});

it('Garanti hosted form kart alanı içermez', function () {
    $gw = new GarantiBBVAGateway;
    $res = $gw->initializeHostedPayment(garantiHostedRequest(), garantiHostedAuth());

    expect($res->form_fields)->not->toHaveKey('cardnumber');
    expect($res->form_fields)->not->toHaveKey('cardcvv2');
    expect($res->form_fields)->not->toHaveKey('cardexpiredatemonth');
});

it('Garanti hosted secure3dhash sale3D ile aynı recipe\'i kullanır', function () {
    $gw = new GarantiBBVAGateway;
    $auth = garantiHostedAuth();
    $req = garantiHostedRequest();
    $res = $gw->initializeHostedPayment($req, $auth);

    $amount = StringHelper::toKurus($req->sale_info->amount);
    $installment = '';

    $hashedPassword = strtoupper(hash('sha1',
        $auth->merchant_password . str_pad((string) ((int) $auth->merchant_user), 9, '0', STR_PAD_LEFT)
    ));
    $expected = strtoupper(hash('sha1',
        $auth->merchant_user . $req->order_number . $amount .
        $req->success_url . $req->fail_url .
        'sales' . $installment . $auth->merchant_storekey . $hashedPassword
    ));

    expect($res->form_fields['secure3dhash'])->toBe($expected);
});

it('Garanti hosted resolveHostedPayment mdstatus=1 + Response=Approved → Success', function () {
    $gw = new GarantiBBVAGateway;
    $payload = [
        'oid' => 'ORDER-GAR-1',
        'mdstatus' => '1',
        'response' => 'Approved',
        'authcode' => 'AUTH-9988',
    ];
    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'ORDER-GAR-1', payload: $payload), garantiHostedAuth());

    expect($res->status)->toBe(SaleResponseStatus::Success);
    expect($res->transaction_id)->toBe('AUTH-9988');
});

it('Garanti hosted resolveHostedPayment mdstatus!=1 → Error', function () {
    $gw = new GarantiBBVAGateway;
    $payload = ['oid' => 'X', 'mdstatus' => '0', 'mderrormessage' => 'auth fail'];
    $res = $gw->resolveHostedPayment(new HostedPaymentCallback(order_number: 'X', payload: $payload), garantiHostedAuth());

    expect($res->status)->toBe(SaleResponseStatus::Error);
    expect($res->message)->toBe('auth fail');
});
